import { Search, TableCard } from '@woocommerce/components/build';
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useHistory, useLocation } from 'react-router-dom';
import { getRow } from './get-row';
import { Workflow, WorkflowStatus } from './workflow';

type Props = {
  workflows: Workflow[];
  loading: boolean;
};

type FilterTab = {
  name: string;
  title?: JSX.Element;
  classname: string;
};

export function AutomationListing({ workflows, loading }: Props): JSX.Element {
  const history = useHistory();
  const location = useLocation();
  const pageSearch = new URLSearchParams(location.search);

  const updateUrlSearchString = (keysAndValues: Record<string, string>) => {
    Object.keys(keysAndValues).forEach((key) => {
      pageSearch.set(key, keysAndValues[key]);
      if (['per_page', 'status'].includes(key)) {
        pageSearch.delete('paged');
      }
    });
    history.replace({ search: pageSearch.toString() });
  };

  const groupedWorkflows = {};
  workflows.forEach((workflow) => {
    if (!groupedWorkflows[workflow.status]) {
      groupedWorkflows[workflow.status] = [];
    }
    groupedWorkflows[workflow.status].push(workflow);
  });

  const tabLabels = [
    { status: WorkflowStatus.ACTIVE, label: 'Active' },
    { status: WorkflowStatus.INACTIVE, label: 'Inactive' },
    { status: WorkflowStatus.DRAFT, label: 'Draft' },
    { status: WorkflowStatus.TRASH, label: 'Trash' },
  ];

  const tabs: FilterTab[] = [
    {
      name: 'all',
      title: (
        <>
          <span>All</span>{' '}
          <span className="count">{workflows.length.toString()}</span>
        </>
      ),
      classname: 'tab-all',
    },
  ];

  tabLabels.forEach((tabInfo) => {
    const tab: FilterTab = {
      name: tabInfo.status,
      classname: `tab-${tabInfo.status}`,
    };

    if (
      groupedWorkflows[tabInfo.status] &&
      groupedWorkflows[tabInfo.status].length > 0
    ) {
      tab.title = (
        <>
          <span>{tabInfo.label}</span>{' '}
          <span className="count">
            {groupedWorkflows[tabInfo.status].length}
          </span>
        </>
      );
    } else {
      tab.title = <span>{tabInfo.label}</span>;
    }

    tabs.push(tab);
  });

  const tableHeaders = [
    { key: 'name', label: __('Name', 'mailpoet') },
    { key: 'subscribers', label: __('Subscribers', 'mailpoet') },
    { key: 'status', label: __('Status', 'mailpoet') },
    { key: 'edit' },
    { key: 'more' },
  ];

  return (
    <TabPanel
      className="mailpoet-filter-tab-panel"
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore - the Tab type actually expects a string for titles but won't render HTML,
      // making it very difficult to style the count badges. It seems to be compatible wtih JSX
      // elements, however.
      tabs={tabs}
      onSelect={(tabName) => {
        updateUrlSearchString({ status: tabName });
      }}
      initialTabName={pageSearch.get('status') || 'all'}
    >
      {(tab) => {
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
      }}
    </TabPanel>
  );
}
