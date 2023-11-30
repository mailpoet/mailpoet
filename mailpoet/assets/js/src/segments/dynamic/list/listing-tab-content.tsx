import { ComponentProps, useEffect, useMemo } from 'react';
import { TableCard, TextControl } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';
import { storeName } from 'segments/dynamic/store';
import { __ } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';
import { DynamicSegment, DynamicSegmentQuery } from 'segments/types';
import { getRow } from 'segments/dynamic/list/get-row';
import {
  updateDynamicQuery,
  updateDynamicQueryFromLocation,
} from './listing-helpers';

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

type ListingTableProps = {
  tab: {
    name: string;
  };
};
export function ListingTabContent({ tab }: ListingTableProps): JSX.Element {
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
      dynamicSegmentQuery !== null ? dynamicSegmentQuery.sort_by : 'updated_at',
    order:
      dynamicSegmentQuery !== null ? dynamicSegmentQuery.sort_order : 'desc',
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
    <>
      <TextControl
        className="mailpoet-segments-listing-search"
        placeholder={__('Search', 'mailpoet')}
        onChange={(value) => {
          updateDynamicQuery({
            search: value,
            offset: 0,
          });
        }}
      />
      <TableCard
        className="mailpoet-segments-listing"
        title=""
        isLoading={dynamicSegments === null}
        headers={
          // typed as mutable so doesn't accept our const (readonly) type
          tableHeaders as unknown as ComponentProps<typeof TableCard>['headers']
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
    </>
  );
}
