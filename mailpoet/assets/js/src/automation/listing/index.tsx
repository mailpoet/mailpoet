import { Search, TableCard } from '@woocommerce/components/build';
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useCallback, useMemo } from 'react';
import { useHistory, useLocation } from 'react-router-dom';
import { getRow } from './get-row';
import { Workflow, WorkflowStatus } from './workflow';

type Props = {
  workflows: Workflow[];
  loading: boolean;
};

const filterTabs = [
  {
    name: 'all',
    title: 'All',
    className: 'mailpoet-tab-all',
  },
  {
    name: WorkflowStatus.ACTIVE,
    title: 'Active',
    className: 'mailpoet-tab-active',
  },
  {
    name: WorkflowStatus.INACTIVE,
    title: 'Inactive',
    className: 'mailpoet-tab-inactive',
  },
  {
    name: WorkflowStatus.DRAFT,
    title: 'Draft',
    className: 'mailpoet-tab-draft',
  },
  {
    name: WorkflowStatus.TRASH,
    title: 'Trash',
    className: 'mailpoet-tab-trash',
  },
] as const;

const tableHeaders = [
  { key: 'name', label: __('Name', 'mailpoet') },
  { key: 'subscribers', label: __('Subscribers', 'mailpoet') },
  { key: 'status', label: __('Status', 'mailpoet') },
  { key: 'edit' },
  { key: 'more' },
] as const;

export function AutomationListing({ workflows, loading }: Props): JSX.Element {
  const history = useHistory();
  const location = useLocation();
  const pageSearch = useMemo(
    () => new URLSearchParams(location.search),
    [location],
  );

  const updateUrlSearchString = useCallback(
    (search: Record<string, string>) => {
      const newSearch = new URLSearchParams({
        ...Object.fromEntries(pageSearch.entries()),
        ...search,
      });
      const changedKeys = Object.keys(search);
      if (
        changedKeys.includes('status') ||
        changedKeys.includes('per_page') ||
        newSearch.get('paged') === '1'
      ) {
        newSearch.delete('paged');
      }
      history.push({ search: newSearch.toString() });
    },
    [pageSearch, history],
  );

  const groupedWorkflows = useMemo<Record<string, Workflow[]>>(() => {
    const grouped = {};
    workflows.forEach((workflow) => {
      if (!grouped[workflow.status]) {
        grouped[workflow.status] = [];
      }
      grouped[workflow.status].push(workflow);
    });
    return grouped;
  }, [workflows]);

  const tabs = useMemo(
    () =>
      filterTabs.map((filterTab) => {
        const count =
          filterTab.name === 'all'
            ? workflows.length
            : (groupedWorkflows[filterTab.name] || []).length;
        return {
          name: filterTab.name,
          title:
            count > 0 ? (
              <>
                <span>{filterTab.title}</span>
                <span className="count">{count}</span>
              </>
            ) : (
              <span>{filterTab.title}</span>
            ),
          className: filterTab.className,
        };
      }),
    [workflows, groupedWorkflows],
  );

  const tabRenderer = useCallback(
    (tab) => {
      const filteredWorkflows: Workflow[] =
        tab.name === 'all' ? workflows : groupedWorkflows[tab.name] ?? [];
      const rowsPerPage = parseInt(pageSearch.get('per_page') || '25', 10);
      const currentPage = parseInt(pageSearch.get('paged') || '1', 10);
      const start = (currentPage - 1) * rowsPerPage;
      const rows = filteredWorkflows
        .map((workflow) => getRow(workflow))
        .slice(start, start + rowsPerPage);

      return (
        <TableCard
          className="mailpoet-automation-listing"
          title=""
          isLoading={loading}
          headers={tableHeaders}
          rows={rows}
          rowKey={(_, i) => filteredWorkflows[i].id}
          rowsPerPage={rowsPerPage}
          onQueryChange={(key) => (value) => {
            updateUrlSearchString({ [key]: value });
          }}
          totalRows={filteredWorkflows.length}
          query={Object.fromEntries(pageSearch)}
          hasSearch
          showMenu={false}
          actions={[
            <Search
              className="mailpoet-automation-listing-search"
              allowFreeTextSearch
              inlineTags
              key="search"
              // onChange={ onSearchChange }
              // placeholder={
              //  labels.placeholder ||
              //  __( 'Search by item name', 'woocommerce' )
              // }
              // selected={ searchedLabels }
              type="custom"
              disabled={loading || workflows.length === 0}
              autocompleter={{}}
            />,
          ]}
        />
      );
    },
    [workflows, groupedWorkflows, pageSearch, loading, updateUrlSearchString],
  );

  return (
    <TabPanel
      className="mailpoet-filter-tab-panel"
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore - the Tab type actually expects a string for titles but won't render HTML,
      // making it very difficult to style the count badges. It seems to be compatible with JSX
      // elements, however.
      tabs={tabs}
      onSelect={(tabName) => {
        if (pageSearch.get('status') !== tabName) {
          updateUrlSearchString({ status: tabName });
        }
      }}
      initialTabName={pageSearch.get('status') || 'all'}
      key={pageSearch.get('status')} // Force re-render on browser forward/back
    >
      {tabRenderer}
    </TabPanel>
  );
}
