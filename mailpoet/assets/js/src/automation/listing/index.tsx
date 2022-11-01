import { Search, TableCard } from '@woocommerce/components/build';
import { Button, Flex, TabPanel } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';
import { useCallback, useEffect, useLayoutEffect, useMemo } from 'react';
import { useHistory, useLocation } from 'react-router-dom';
import { plusIcon } from 'common/button/icon/plus';
import { getRow } from './get-row';
import { storeName } from './store';
import { Workflow, WorkflowStatus } from './workflow';
import { MailPoet } from '../../mailpoet';

const tabConfig = [
  {
    name: 'all',
    title: __('All', 'mailpoet'),
    className: 'mailpoet-tab-all',
  },
  {
    name: WorkflowStatus.ACTIVE,
    title: __('Active', 'mailpoet'),
    className: 'mailpoet-tab-active',
  },
  {
    name: WorkflowStatus.INACTIVE,
    title: __('Inactive', 'mailpoet'),
    className: 'mailpoet-tab-inactive',
  },
  {
    name: WorkflowStatus.DRAFT,
    title: _x('Draft', 'noun', 'mailpoet'),
    className: 'mailpoet-tab-draft',
  },
  {
    name: WorkflowStatus.TRASH,
    title: _x('Trash', 'noun', 'mailpoet'),
    className: 'mailpoet-tab-trash',
  },
] as const;

const tableHeaders = [
  {
    key: 'name',
    label: __('Name', 'mailpoet'),
    cellClassName: 'mailpoet-automation-listing-cell-name',
  },
  { key: 'subscribers', label: __('Subscribers', 'mailpoet') },
  { key: 'status', label: __('Status', 'mailpoet') },
  { key: 'actions' },
] as const;

export function AutomationListingHeader(): JSX.Element {
  return (
    <Flex className="mailpoet-automation-listing-heading">
      <h1 className="wp-heading-inline">{__('Automations', 'mailpoet')}</h1>
      <Button
        href={MailPoet.urls.automationTemplates}
        icon={plusIcon}
        variant="primary"
        className="mailpoet-add-new-button"
      >
        {__('New automation', 'mailpoet')}
      </Button>
    </Flex>
  );
}

export function AutomationListing(): JSX.Element {
  const history = useHistory();
  const location = useLocation();
  const pageSearch = useMemo(
    () => new URLSearchParams(location.search),
    [location],
  );

  const workflows = useSelect((select) => select(storeName).getWorkflows());
  const { loadWorkflows } = useDispatch(storeName);

  const status = pageSearch.get('status');

  useEffect(() => {
    loadWorkflows();
  }, [loadWorkflows]);

  // focus tab button on status change (needed due to the force re-mount below)
  useLayoutEffect(() => {
    if (status) {
      document.querySelector<HTMLElement>(`.mailpoet-tab-${status}`)?.focus();
    }
  }, [status]);

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
    const grouped = { all: [] };
    (workflows ?? []).forEach((workflow) => {
      if (!grouped[workflow.status]) {
        grouped[workflow.status] = [];
      }
      grouped[workflow.status].push(workflow);
      if (workflow.status !== WorkflowStatus.TRASH) {
        grouped.all.push(workflow);
      }
    });
    return grouped;
  }, [workflows]);

  const tabs = useMemo(
    () =>
      tabConfig.map((tab) => {
        const count = (groupedWorkflows[tab.name] ?? []).length;
        return {
          name: tab.name,
          title: (
            <>
              <span>{tab.title}</span>
              {count > 0 && <span className="count">{count}</span>}
            </>
          ) as any, // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports JSX
          className: tab.className,
        };
      }),
    [groupedWorkflows],
  );

  const renderTabs = useCallback(
    (tab) => {
      const filteredWorkflows: Workflow[] = groupedWorkflows[tab.name] ?? [];
      const rowsPerPage = parseInt(pageSearch.get('per_page') ?? '25', 10);
      const currentPage = parseInt(pageSearch.get('paged') ?? '1', 10);
      const start = (currentPage - 1) * rowsPerPage;
      const rows = filteredWorkflows
        .map((workflow) => getRow(workflow))
        .slice(start, start + rowsPerPage);

      return (
        <TableCard
          className="mailpoet-automation-listing"
          title=""
          isLoading={!workflows}
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
              type="custom"
              disabled={!workflows}
              autocompleter={{}}
            />,
          ]}
        />
      );
    },
    [workflows, groupedWorkflows, pageSearch, updateUrlSearchString],
  );

  return (
    <TabPanel
      className="mailpoet-filter-tab-panel"
      tabs={tabs}
      onSelect={(tabName) => {
        if (status !== tabName) {
          updateUrlSearchString({ status: tabName });
        }
      }}
      initialTabName={status ?? 'all'}
      key={status} // force re-mount on history change to switch tab (via "initialTabName")
    >
      {renderTabs}
    </TabPanel>
  );
}
