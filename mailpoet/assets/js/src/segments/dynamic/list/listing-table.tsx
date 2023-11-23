import { ComponentProps, useCallback, useEffect, useMemo } from 'react';
import { TableCard } from '@woocommerce/components';
import { select, dispatch, useSelect } from '@wordpress/data';
import { storeName } from 'segments/dynamic/store';
import { __, _x } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';
import { DynamicSegment, DynamicSegmentQuery } from 'segments/types';
import { TabPanel } from '@wordpress/components';
import { getRow } from 'segments/dynamic/list/get-row';

function updateDynamicQuery(values: Partial<DynamicSegmentQuery>): void {
  const defaultQuery = {
    offset: 0,
    limit: 2,
    filter: {},
    search: '',
    sort_by: 'updated_at',
    sort_order: 'desc',
    group: 'all',
  };
  const currentQuery = select(storeName).getDynamicSegmentsQuery();
  const query = currentQuery ?? defaultQuery;
  const newQuery = { ...query, ...values };
  if (JSON.stringify(query) === JSON.stringify(newQuery)) {
    return;
  }
  dispatch(storeName).updateDynamicSegmentsQuery(newQuery);
}

function updateDynamicQueryFromLocation(pathname: string): void {
  const pathElements = pathname.split('/');
  if (pathElements[1] !== 'segments') {
    return;
  }
  const defaultQuery = {
    offset: 0,
    limit: 2,
    filter: {},
    search: '',
    sort_by: 'updated_at',
    sort_order: 'desc',
    group: 'all',
  };
  const currentQuery = select(storeName).getDynamicSegmentsQuery();
  const query = currentQuery !== null ? currentQuery : defaultQuery;
  const queryKeys = Object.keys(query);

  for (
    let pathElementsIndex = 0;
    pathElementsIndex < pathElements.length;
    pathElementsIndex += 1
  ) {
    for (
      let queryKeysIndex = 0;
      queryKeysIndex < queryKeys.length;
      queryKeysIndex += 1
    ) {
      if (
        pathElements[pathElementsIndex].startsWith(
          `${queryKeys[queryKeysIndex]}[`,
        )
      ) {
        query[queryKeys[queryKeysIndex]] = pathElements[pathElementsIndex]
          .replace(`${queryKeys[queryKeysIndex]}[`, '')
          .replace(']', '');
      }
    }
  }
  updateDynamicQuery(query);
}

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
    key: 'name',
    label: __('Segment', 'mailpoet'),
    cellClassName: 'mailpoet-listing-name',
    isSortable: true,
  },
  {
    key: 'subscribers',
    label: __('Number of subscribers', 'mailpoet'),
    isLeftAligned: false,
    isNumeric: true,
    isSortable: false,
  },
  {
    key: 'subscribed',
    label: __('Subscribed', 'mailpoet'),
    isLeftAligned: false,
    isNumeric: true,
    isSortable: false,
  },
  {
    key: 'updated_at',
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
  const { dynamicSegments, dynamicSegmentQuery, dynamicSegmentsGroups } =
    useSelect((s) => ({
      dynamicSegments: s(storeName).getDynamicSegments(),
      dynamicSegmentQuery: s(storeName).getDynamicSegmentsQuery(),
      dynamicSegmentsGroups: s(storeName).getDynamicSegmentsGroups(),
    }));

  useEffect(() => {
    if (dynamicSegmentQuery === null) {
      updateDynamicQueryFromLocation(location.pathname);
    }
  }, [dynamicSegmentQuery, location]);

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
        const currentGroup = dynamicSegmentsGroups?.find(
          (group) => tab.name === group.name,
        );
        const count = currentGroup?.count ?? 0;
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
    [dynamicSegmentsGroups],
  );

  const renderTabs = useCallback(
    (tab) => {
      const filteredDynamicSegments: DynamicSegment[] =
        groupedDynamicSegments[tab.name] ?? [];
      let currentGroup = null;
      if (dynamicSegmentsGroups) {
        currentGroup = dynamicSegmentsGroups.find(
          (group) => tab.name === group.name,
        );
      }

      const rowsPerPage =
        dynamicSegmentQuery !== null ? dynamicSegmentQuery.limit : 10;
      const rows = filteredDynamicSegments.map((dynamicSegment) =>
        getRow(dynamicSegment),
      );
      const totalRows = currentGroup ? currentGroup.count : 0;
      const tableQueryParams = {
        orderby:
          dynamicSegmentQuery !== null
            ? dynamicSegmentQuery.sort_by
            : 'updated_at',
        order:
          dynamicSegmentQuery !== null
            ? dynamicSegmentQuery.sort_order
            : 'desc',
        page:
          dynamicSegmentQuery !== null
            ? dynamicSegmentQuery.offset / dynamicSegmentQuery.limit + 1
            : 1,
        per_page: dynamicSegmentQuery !== null ? dynamicSegmentQuery.limit : 25,
        paged:
          dynamicSegmentQuery !== null
            ? dynamicSegmentQuery.offset / dynamicSegmentQuery.limit + 1
            : 1,
      };
      return (
        <TableCard
          className="mailpoet-segments-listing"
          title=""
          isLoading={dynamicSegments === null}
          headers={
            // typed as mutable so doesn't accept our const (readonly) type
            tableHeaders as unknown as ComponentProps<
              typeof TableCard
            >['headers']
          }
          rows={rows}
          onQueryChange={(query) => (param) => {
            if (dynamicSegmentQuery === null) {
              return;
            }
            if (query === 'paged') {
              updateDynamicQuery({
                offset: dynamicSegmentQuery.limit * (param - 1),
              });
            }
            if (query === 'per_page') {
              updateDynamicQuery({
                limit: param,
                offset: 0,
              });
            }
            if (query === 'sort') {
              const newParams = {
                offset: 0,
                sort_by: param,
              } as Partial<DynamicSegmentQuery>;
              if (dynamicSegmentQuery.sort_by === param) {
                newParams.sort_order =
                  dynamicSegmentQuery.sort_order === 'asc' ? 'desc' : 'asc';
              }
              updateDynamicQuery(newParams);
            }
          }}
          query={tableQueryParams}
          rowKey={(_, i) => dynamicSegments[i].id}
          rowsPerPage={rowsPerPage}
          totalRows={totalRows}
          showMenu={false}
        />
      );
    },
    [
      dynamicSegments,
      dynamicSegmentQuery,
      dynamicSegmentsGroups,
      groupedDynamicSegments,
    ],
  );

  return (
    <TabPanel
      className="mailpoet-filter-tab-panel"
      tabs={tabs}
      initialTabName="all"
      onSelect={(tab) => {
        updateDynamicQuery({ group: tab });
      }}
    >
      {renderTabs}
    </TabPanel>
  );
}
