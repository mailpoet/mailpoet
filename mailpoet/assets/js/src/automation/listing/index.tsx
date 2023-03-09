import { TableCard } from '@woocommerce/components/build';
import { Button, Flex, TabPanel } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';
import { useCallback, useEffect, useLayoutEffect, useMemo } from 'react';
import { useHistory, useLocation } from 'react-router-dom';
import { plusIcon } from 'common/button/icon/plus';
import { getRow } from './get-row';
import { storeName } from './store';
import { Automation, AutomationStatus } from './automation';
import { MailPoet } from '../../mailpoet';

const tabConfig = [
  {
    name: 'all',
    title: __('All', 'mailpoet'),
    className: 'mailpoet-tab-all',
  },
  {
    name: AutomationStatus.ACTIVE,
    title: __('Active', 'mailpoet'),
    className: 'mailpoet-tab-active',
  },
  {
    name: AutomationStatus.DRAFT,
    title: _x('Draft', 'noun', 'mailpoet'),
    className: 'mailpoet-tab-draft',
  },
  {
    name: AutomationStatus.TRASH,
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

  const automations = useSelect((select) => select(storeName).getAutomations());
  const { loadAutomations } = useDispatch(storeName);

  const status = pageSearch.get('status');

  useEffect(() => {
    loadAutomations();
  }, [loadAutomations]);

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

  const groupedAutomations = useMemo<Record<string, Automation[]>>(() => {
    const grouped = { all: [] };
    (automations ?? []).forEach((automation) => {
      if (!grouped[automation.status]) {
        grouped[automation.status] = [];
      }
      grouped[automation.status].push(automation);
      if (automation.status !== AutomationStatus.TRASH) {
        grouped.all.push(automation);
      }
    });
    return grouped;
  }, [automations]);

  const tabs = useMemo(
    () =>
      tabConfig.map((tab) => {
        const count = (groupedAutomations[tab.name] ?? []).length;
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
    [groupedAutomations],
  );

  const renderTabs = useCallback(
    (tab) => {
      const filteredAutomations: Automation[] =
        groupedAutomations[tab.name] ?? [];
      const rowsPerPage = parseInt(pageSearch.get('per_page') ?? '25', 10);
      const currentPage = parseInt(pageSearch.get('paged') ?? '1', 10);
      const start = (currentPage - 1) * rowsPerPage;
      const rows = filteredAutomations
        .map((automation) => getRow(automation))
        .slice(start, start + rowsPerPage);

      return (
        <TableCard
          className="mailpoet-automation-listing"
          title=""
          isLoading={!automations}
          headers={tableHeaders}
          rows={rows}
          rowKey={(_, i) => filteredAutomations[i].id}
          rowsPerPage={rowsPerPage}
          onQueryChange={(key) => (value) => {
            updateUrlSearchString({ [key]: value });
          }}
          totalRows={filteredAutomations.length}
          query={Object.fromEntries(pageSearch)}
          showMenu={false}
        />
      );
    },
    [automations, groupedAutomations, pageSearch, updateUrlSearchString],
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
