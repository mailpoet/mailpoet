import { ComponentProps, useCallback, useEffect, useMemo } from 'react';
import { TableCard } from '@woocommerce/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from 'segments/dynamic/store';
import { __, _x } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';
import { DynamicSegment } from 'segments/types';
import { TabPanel } from '@wordpress/components';
import { getRow } from 'segments/dynamic/list/get-row';

const tabConfig = [
  {
    name: 'all',
    title: __('All', 'mailpoet'),
    className: 'mailpoet-tab-all',
  },
  {
    name: 'trash',
    title: _x('Trash', 'noun', 'mailpoet'),
    className: 'mailpoet-tab-trash',
  },
] as const;

const tableHeaders = [
  {
    key: 'checkbox',
    label: <input type="checkbox" />,
    cellClassName: 'mailpoet-listing-checkbox',
  },
  {
    key: 'segment',
    label: __('Segment', 'mailpoet'),
    cellClassName: 'mailpoet-listing-name',
    isSortable: true,
  },
  {
    key: 'subscribers',
    label: __('Number of subscribers', 'mailpoet'),
    isLeftAligned: false,
    isNumeric: true,
    isSortable: true,
  },
  {
    key: 'subscribed',
    label: __('Subscribed', 'mailpoet'),
    isLeftAligned: false,
    isNumeric: true,
    isSortable: true,
  },
  {
    key: 'modified_date',
    label: __('Modified', 'mailpoet'),
    cellClassName: 'mailpoet-listing-modified-date',
    isLeftAligned: false,
    isSortable: true,
  },
  {
    key: 'actions',
    isLeftAligned: false,
  },
] as const;

export function ListingTable(): JSX.Element {
  const location = useLocation();
  const pageSearch = useMemo(
    () => new URLSearchParams(location.search),
    [location],
  );

  const dynamicSegments = useSelect((select) =>
    select(storeName).getDynamicSegments(),
  );
  const { loadDynamicSegments } = useDispatch(storeName);

  useEffect(() => {
    loadDynamicSegments().catch(() => {
      // TODO: check if this is the right away to use loadDynamicSegments and handle errors if it is
    });
  }, [loadDynamicSegments]);

  const groupedDynamicSegments = useMemo<
    Record<string, DynamicSegment[]>
  >(() => {
    const grouped = { all: [], trash: [] };
    (dynamicSegments ?? []).forEach((dynamicSegment) => {
      if (dynamicSegment.deleted_at === null) {
        grouped.all.push(dynamicSegment);
      } else {
        grouped.trash.push(dynamicSegment);
      }
    });
    return grouped;
  }, [dynamicSegments]);

  const tabs = useMemo(
    () =>
      tabConfig.map((tab) => {
        const count = (groupedDynamicSegments[tab.name] ?? []).length;

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
    [groupedDynamicSegments],
  );

  const renderTabs = useCallback(
    (tab) => {
      const filteredDynamicSegments: DynamicSegment[] =
        groupedDynamicSegments[tab.name] ?? [];
      const rowsPerPage = parseInt(pageSearch.get('per_page') ?? '25', 10);
      const currentPage = parseInt(pageSearch.get('paged') ?? '1', 10);
      const start = (currentPage - 1) * rowsPerPage;
      const rows = filteredDynamicSegments
        .map((dynamicSegment) => getRow(dynamicSegment))
        .slice(start, start + rowsPerPage);

      return (
        <TableCard
          className="mailpoet-segments-listing"
          title=""
          isLoading={!dynamicSegments}
          headers={
            // typed as mutable so doesn't accept our const (readonly) type
            tableHeaders as unknown as ComponentProps<
              typeof TableCard
            >['headers']
          }
          rows={rows}
          rowKey={(_, i) => dynamicSegments[i].id}
          rowsPerPage={rowsPerPage}
          totalRows={dynamicSegments.length}
          query={Object.fromEntries(pageSearch)}
          showMenu={false}
        />
      );
    },
    [dynamicSegments, groupedDynamicSegments, pageSearch],
  );

  return (
    <TabPanel
      className="mailpoet-filter-tab-panel"
      tabs={tabs}
      initialTabName="all"
    >
      {renderTabs}
    </TabPanel>
  );
}
