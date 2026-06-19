import type { Filter } from '@wordpress/dataviews';
import { dateRangeFromFilter } from 'common/dataviews';

export const CREATED_AT_FIELD = 'created_at';
export const UPDATED_AT_FIELD = 'updated_at';

export type DynamicSegmentsRequestFilter = {
  created_from?: string;
  created_to?: string;
  updated_from?: string;
  updated_to?: string;
};

function dateRangeKeys(
  fromKey: 'created_from' | 'updated_from',
  toKey: 'created_to' | 'updated_to',
  operator: string,
  value: unknown,
): DynamicSegmentsRequestFilter {
  const { from, to } = dateRangeFromFilter(operator, value);
  return {
    ...(from ? { [fromKey]: from } : {}),
    ...(to ? { [toKey]: to } : {}),
  };
}

/**
 * Translate DataViews native filters into the REST `filter` object the dynamic
 * segments listing endpoint understands (`{ created_from, created_to,
 * updated_from, updated_to }`). The two date columns use distinct keys so they
 * can be filtered independently. Empty filters are omitted.
 */
export function viewFiltersToRequestFilter(
  filters: Filter[] | undefined,
): DynamicSegmentsRequestFilter {
  const result: DynamicSegmentsRequestFilter = {};

  (filters ?? []).forEach((filter) => {
    if (filter.field === CREATED_AT_FIELD) {
      Object.assign(
        result,
        dateRangeKeys(
          'created_from',
          'created_to',
          filter.operator,
          filter.value,
        ),
      );
    } else if (filter.field === UPDATED_AT_FIELD) {
      Object.assign(
        result,
        dateRangeKeys(
          'updated_from',
          'updated_to',
          filter.operator,
          filter.value,
        ),
      );
    }
  });

  return result;
}
