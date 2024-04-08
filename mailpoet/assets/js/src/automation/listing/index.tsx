import { TableCard } from '@woocommerce/components';
import { Button, TabPanel } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';
import {
  ComponentProps,
  useCallback,
  useEffect,
  useLayoutEffect,
  useMemo,
} from 'react';
import { useHistory, useLocation } from 'react-router-dom';
import { plusIcon } from 'common/button/icon/plus';
import { getRow } from './get-row';
import { AutomationItem, storeName } from './store';
import { Automation, AutomationStatus } from './automation';
import { automationCount, legacyAutomationCount } from '../config';
import { MailPoet } from '../../mailpoet';
import { PageHeader } from '../../common/page-header';

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
    <PageHeader heading={__('Automations', 'mailpoet')}>
      <Button
        href={MailPoet.urls.automationTemplates}
        icon={plusIcon}
        variant="primary"
        className="mailpoet-add-new-button"
      >
        {__('New automation', 'mailpoet')}
      </Button>
    </PageHeader>
  );
}

export function AutomationListing(): JSX.Element {
  const history = useHistory();
  const location = useLocation();
  const pageSearch = useMemo(
    () => new URLSearchParams(location.search),
    [location],
  );

  const automations = useSelect((select) =>
    select(storeName).getAllAutomations(),
  );
  const { loadAutomations, loadLegacyAutomations } = useDispatch(storeName);

  const status = pageSearch.get('status');

  useEffect(() => {
    void loadAutomations();
    void loadLegacyAutomations();
  }, [loadAutomations, loadLegacyAutomations]);

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

  const tabs = useMemo(() => {
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

    return tabConfig.map((tab) => {
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
    });
  }, [groupedAutomations]);

  const renderTabs = useCallback(
    (tab) => {
      const totalCount = automationCount + legacyAutomationCount;
      const filteredAutomations: AutomationItem[] =
        groupedAutomations[tab.name] ?? [];
      const rowsPerPage = parseInt(pageSearch.get('per_page') ?? '25', 10);
      const currentPage = parseInt(pageSearch.get('paged') ?? '1', 10);
      const start = (currentPage - 1) * rowsPerPage;
      const rows = filteredAutomations
        .map((automation) => getRow(automation))
        .slice(start, start + rowsPerPage);

      return (
        <TableCard
          className="mailpoet-listing-card mailpoet-automation-listing"
          title=""
          isLoading={!automations}
          headers={
            // typed as mutable so doesn't accept our const (readonly) type
            tableHeaders as unknown as ComponentProps<
              typeof TableCard
            >['headers']
          }
          rows={rows}
          rowKey={(_, i) =>
            filteredAutomations[i].id *
            (filteredAutomations[i].isLegacy ? -1 : 1)
          }
          rowsPerPage={Math.min(rowsPerPage, totalCount)}
          onQueryChange={(key) => (value) => {
            updateUrlSearchString({ [key]: value });
          }}
          totalRows={automations ? filteredAutomations.length : totalCount}
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
