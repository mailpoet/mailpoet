import type { Filter } from '@wordpress/dataviews';
import type { CustomFieldsRequestFilter } from './types';

export const TYPE_FIELD = 'type';

function toStringArray(value: unknown): string[] {
  if (!Array.isArray(value)) {
    return [];
  }
  return value
    .map((entry) => String(entry).trim())
    .filter((entry) => entry !== '');
}

/**
 * Translate DataViews native filters into the REST `filter` object the custom
 * fields endpoint understands (`{ type }`). The output doubles as the persisted
 * URL/query-string shape.
 */
export function viewFiltersToRequestFilter(
  filters: Filter[] | undefined,
): CustomFieldsRequestFilter {
  const result: CustomFieldsRequestFilter = {};

  (filters ?? []).forEach((filter) => {
    if (filter.field === TYPE_FIELD) {
      const types = toStringArray(filter.value);
      if (types.length > 0) {
        result.type = types;
      }
    }
  });

  return result;
}

/**
 * Seed DataViews native filters from a parsed URL filter state.
 */
export function requestFilterToViewFilters(
  filter: CustomFieldsRequestFilter,
): Filter[] {
  const filters: Filter[] = [];

  if (filter.type && filter.type.length > 0) {
    filters.push({ field: TYPE_FIELD, operator: 'isAny', value: filter.type });
  }

  return filters;
}
