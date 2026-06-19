import type { Filter } from '@wordpress/dataviews';
import { dateRangeFromFilter } from 'common/dataviews';
import type { TagsRequestFilter } from './types';

export const CREATED_AT_FIELD = 'created_at';
export const SUBSCRIBERS_FIELD = 'subscribers';

/**
 * Translate DataViews native filters into the REST `filter` object the tags
 * endpoint understands (`{ from, to, subscribers }`). `subscribers` is the list
 * of selected subscriber-count bucket values. The output doubles as the
 * persisted URL/query-string shape (see buildTagsUrl).
 */
export function viewFiltersToRequestFilter(
  filters: Filter[] | undefined,
): TagsRequestFilter {
  const result: TagsRequestFilter = {};

  (filters ?? []).forEach((filter) => {
    if (filter.field === CREATED_AT_FIELD) {
      const { from, to } = dateRangeFromFilter(filter.operator, filter.value);
      if (from) {
        result.from = from;
      }
      if (to) {
        result.to = to;
      }
    } else if (filter.field === SUBSCRIBERS_FIELD) {
      const values = (Array.isArray(filter.value) ? filter.value : [])
        .map((entry) => String(entry))
        .filter((entry) => entry !== '');
      if (values.length > 0) {
        result.subscribers = values;
      }
    }
  });

  return result;
}

function datesToFilter(from?: string, to?: string): Filter | null {
  if (from && to) {
    return from === to
      ? { field: CREATED_AT_FIELD, operator: 'on', value: from }
      : { field: CREATED_AT_FIELD, operator: 'between', value: [from, to] };
  }
  // Stored `from`/`to` are inclusive whole-day boundaries, so seed the inclusive
  // operators; the exclusive `after`/`before` would shift the day on re-serialize.
  if (from) {
    return { field: CREATED_AT_FIELD, operator: 'afterInc', value: from };
  }
  if (to) {
    return { field: CREATED_AT_FIELD, operator: 'beforeInc', value: to };
  }
  return null;
}

/**
 * Seed DataViews native filters from a parsed URL filter state. Maps a from+to
 * range to a single `between` filter so it stays editable as one native control.
 */
export function requestFilterToViewFilters(
  filter: TagsRequestFilter,
): Filter[] {
  const filters: Filter[] = [];

  const dateFilter = datesToFilter(filter.from, filter.to);
  if (dateFilter) {
    filters.push(dateFilter);
  }
  if (filter.subscribers && filter.subscribers.length > 0) {
    filters.push({
      field: SUBSCRIBERS_FIELD,
      operator: 'isAny',
      value: filter.subscribers,
    });
  }

  return filters;
}
