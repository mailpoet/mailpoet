import { Search, TableCard } from '@woocommerce/components/build';
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useCallback, useLayoutEffect, useMemo } from 'react';
import { useHistory, useLocation } from 'react-router-dom';
import { getRow } from './get-row';
import { Workflow, WorkflowStatus } from './workflow';

type Props = {
  workflows: Workflow[];
  loading: boolean;
};

export function AutomationListing({ workflows, loading }: Props): JSX.Element {
  const history = useHistory();
  const location = useLocation();
  const pageSearch = useMemo(
    () => new URLSearchParams(location.search),
    [location],
  );

  const updateUrlSearchString = useCallback(
    (keysAndValues: Record<string, string>) => {
      Object.keys(keysAndValues).forEach((key) => {
        pageSearch.set(key, keysAndValues[key]);
        if (['per_page', 'status'].includes(key)) {
          pageSearch.delete('paged');
        }
      });
      history.push({ search: pageSearch.toString() });
    },
    [pageSearch, history],
  );

  const groupedWorkflows = useMemo(() => {
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
      [
        {
          name: 'all',
          title: 'All',
          className: 'mailpoet-tab-all',
        },
      ].concat(
        [
          { status: WorkflowStatus.ACTIVE, label: 'Active' },
          { status: WorkflowStatus.INACTIVE, label: 'Inactive' },
          { status: WorkflowStatus.DRAFT, label: 'Draft' },
          { status: WorkflowStatus.TRASH, label: 'Trash' },
        ].map((tabLabel) => ({
          name: tabLabel.status,
          title: tabLabel.label,
          className: `mailpoet-tab-${tabLabel.status}`,
        })),
      ),
    [],
  );

  // Add counts to tabs. The tab `title` must be a non-HTML string, so to avoid
  // a type mismatch we're adding the counts dynamically after the fact.
  useLayoutEffect(() => {
    tabs.forEach((tab) => {
      const count =
        tab.name === 'all'
          ? workflows.length
          : (groupedWorkflows[tab.name] || []).length;

      if (count < 1) {
        return;
      }

      const tabElement = document.querySelector(`.${tab.className}`);

      if (!tabElement) {
        return;
      }

      const existingCount = tabElement.querySelector('.count');
      if (existingCount) {
        tabElement.removeChild(existingCount);
      }

      const countElement = document.createElement('span');
      countElement.classList.add('count');
      countElement.innerHTML = count;
      tabElement.appendChild(countElement);
    });
  }, [workflows, groupedWorkflows, tabs]);

  const tableHeaders = useMemo(
    () => [
      { key: 'name', label: __('Name', 'mailpoet') },
      { key: 'subscribers', label: __('Subscribers', 'mailpoet') },
      { key: 'status', label: __('Status', 'mailpoet') },
      { key: 'edit' },
      { key: 'more' },
    ],
    [],
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
    [
      workflows,
      groupedWorkflows,
      pageSearch,
      loading,
      tableHeaders,
      updateUrlSearchString,
    ],
  );

  return (
    <TabPanel
      className="mailpoet-filter-tab-panel"
      tabs={tabs}
      onSelect={(tabName) => {
        updateUrlSearchString({ status: tabName });
      }}
      initialTabName={pageSearch.get('status') || 'all'}
      key={pageSearch.get('status')} // Force re-render on browser forward/back
    >
      {tabRenderer}
    </TabPanel>
  );
}
