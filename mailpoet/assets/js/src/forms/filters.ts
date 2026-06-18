import type { Filter } from '@wordpress/dataviews';
import { dateRangeFromFilter } from 'common/dataviews';

export const STATUS_FIELD = 'status';
export const CREATED_AT_FIELD = 'created_at';
export const UPDATED_AT_FIELD = 'updated_at';

export type FormsRequestFilter = {
  status?: string[];
  created_from?: string;
  created_to?: string;
  updated_from?: string;
  updated_to?: string;
};

function toStatusArray(value: unknown): string[] {
  const values = Array.isArray(value) ? value : [value];
  return values
    .map((entry) => String(entry))
    .filter((entry) => entry === 'enabled' || entry === 'disabled');
}

function dateRangeKeys(
  fromKey: 'created_from' | 'updated_from',
  toKey: 'created_to' | 'updated_to',
  operator: string,
  value: unknown,
): FormsRequestFilter {
  const { from, to } = dateRangeFromFilter(operator, value);
  return {
    ...(from ? { [fromKey]: from } : {}),
    ...(to ? { [toKey]: to } : {}),
  };
}

/**
 * Translate DataViews native filters into the REST `filter` object the forms
 * listing endpoint understands (`{ status, created_from, created_to,
 * updated_from, updated_to }`). The two date columns use distinct keys so they
 * can be filtered independently. Empty filters are omitted so an inactive
 * control never sends a key.
 */
export function viewFiltersToRequestFilter(
  filters: Filter[] | undefined,
): FormsRequestFilter {
  const result: FormsRequestFilter = {};

  (filters ?? []).forEach((filter) => {
    if (filter.field === STATUS_FIELD) {
      const statuses = toStatusArray(filter.value);
      if (statuses.length > 0) {
        result.status = statuses;
      }
    } else if (filter.field === CREATED_AT_FIELD) {
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
