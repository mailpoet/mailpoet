import { ComponentProps, useEffect, useMemo, useState } from 'react';
import { TableCard } from '@woocommerce/components';
import { TextControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { storeName } from 'segments/dynamic/store';
import { __ } from '@wordpress/i18n';
import { DynamicSegment, DynamicSegmentAction } from 'segments/types';
import { getRow } from 'segments/dynamic/list/get-row';
import { BulkActions } from './bulk-actions';
import { DynamicSegmentsListNotices } from './notices';
import { ActionConfirm } from './action-confirm';
import { useSegmentsQuery, updateSegmentsQuery } from './query';
import useDebouncedInput from '../../../common/use-debounced-input';

const totalDynamicSegmentCount = window.mailpoet_dynamic_segment_count;

function SelectAll(): JSX.Element {
  const { dynamicSegments } = useSelect((s) => ({
    dynamicSegments: s(storeName).getDynamicSegments(),
  }));
  const allSelected =
    dynamicSegments !== null &&
    dynamicSegments.filter((segment) => segment.selected).length ===
      dynamicSegments.length &&
    dynamicSegments.length > 0;
  return (
    <input
      checked={allSelected}
      type="checkbox"
      data-automation-id="select_all"
      onChange={() => {
        if (allSelected) {
          void dispatch(storeName).unselectAllDynamicSections();
        } else {
          void dispatch(storeName).selectAllDynamicSections();
        }
      }}
    />
  );
}

const tableHeaders = [
  {
    key: 'checkbox',
    label: <SelectAll />,
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
  const [currentAction, setCurrentAction] =
    useState<DynamicSegmentAction>(null);
  const [currentSelected, setCurrentSelected] = useState<DynamicSegment[]>([]);

  const { dynamicSegments, dynamicSegmentsCount } = useSelect((select) => ({
    dynamicSegments: select(storeName).getDynamicSegments(),
    dynamicSegmentsCount: select(storeName).getDynamicSegmentsCount(),
  }));

  const segmentsQuery = useSegmentsQuery();

  const [search, setSearch, debouncedSearch] = useDebouncedInput(
    segmentsQuery.search ?? '',
  );

  useEffect(() => {
    updateSegmentsQuery({ search: debouncedSearch, offset: 0 });
  }, [debouncedSearch]);

  useEffect(() => {
    void dispatch(storeName).loadDynamicSegments(segmentsQuery);
  }, [segmentsQuery]);

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

  const rows = filteredDynamicSegments.map((dynamicSegment) =>
    getRow(dynamicSegment, tab.name, (action, segment) => {
      setCurrentSelected([segment]);
      setCurrentAction(action);
    }),
  );

  const tableQueryParams = {
    orderby: segmentsQuery.sort_by,
    order: segmentsQuery.sort_order,
    page: segmentsQuery.offset / segmentsQuery.limit + 1,
    per_page: segmentsQuery.limit,
    paged: segmentsQuery.offset / segmentsQuery.limit + 1,
  };

  return (
    <>
      <div className="mailpoet-segments-listing-header">
        <DynamicSegmentsListNotices />
        <BulkActions
          tab={tab}
          onClick={(selected, action) => {
            setCurrentSelected(selected);
            setCurrentAction(action);
          }}
        />
        <TextControl
          className="mailpoet-segments-listing-search"
          placeholder={__('Search', 'mailpoet')}
          onChange={setSearch}
          value={search}
        />
      </div>

      <TableCard
        className="mailpoet-listing-card mailpoet-segments-listing"
        title=""
        isLoading={dynamicSegments === null}
        headers={
          // typed as mutable so doesn't accept our const (readonly) type
          tableHeaders as unknown as ComponentProps<typeof TableCard>['headers']
        }
        rows={rows}
        onQueryChange={(query) => (param) => {
          if (query === 'paged') {
            updateSegmentsQuery({
              offset: segmentsQuery.limit * (param - 1),
            });
          }
          if (query === 'per_page') {
            updateSegmentsQuery({
              limit: parseInt(param as string, 10),
              offset: 0,
            });
          }
          if (query === 'sort') {
            updateSegmentsQuery({
              offset: 0,
              sort_by: param,
              sort_order:
                segmentsQuery.sort_by === param &&
                segmentsQuery.sort_order === 'desc'
                  ? 'asc'
                  : 'desc',
            });
          }
        }}
        query={tableQueryParams}
        rowKey={(_, i) => dynamicSegments[i].id}
        rowsPerPage={Math.min(segmentsQuery.limit, totalDynamicSegmentCount)}
        totalRows={dynamicSegmentsCount ?? totalDynamicSegmentCount}
        showMenu={false}
      />

      <ActionConfirm
        action={currentAction}
        selected={currentSelected}
        onClose={() => setCurrentAction(null)}
      />
    </>
  );
}
