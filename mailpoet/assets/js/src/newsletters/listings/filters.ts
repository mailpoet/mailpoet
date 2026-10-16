import type { Filter } from '@wordpress/dataviews';
import {
  dateRangeFromFilter,
  hasOnlyDigits,
  isStrictDateString,
} from 'common/dataviews';

export const STATUS_FIELD = 'status';
export const SEGMENTS_FIELD = 'segments';
export const SENT_AT_FIELD = 'sent_at';
// The Sending Status page filters on its result column.
export const RESULT_FIELD = 'failed';

export type NewslettersRequestFilter = {
  status?: string[];
  segment_ids?: string[];
  segment_operator?: 'isAny' | 'isNone';
  sent_at_from?: string;
  sent_at_to?: string;
};

export type SendingStatusRequestFilter = { result?: string[] };

type SortValue = { field: string; direction: 'asc' | 'desc' };

const STATUSES_BY_TYPE: Record<string, string[]> = {
  standard: ['draft', 'scheduled', 'sending', 'paused', 'sent'],
  notification_history: ['sending', 'paused', 'sent'],
  notification: ['active', 'draft'],
  re_engagement: ['active', 'draft'],
};

const RESULT_VALUES = ['sent', 'failed', 'unprocessed'];

// The status filter exists only on the All and Trash tabs.
export function getAllowedStatuses(type: string, group: string): string[] {
  if (group !== 'all' && group !== 'trash') return [];
  return STATUSES_BY_TYPE[type] ?? [];
}

function isDigits(value: string): boolean {
  return value !== '' && hasOnlyDigits(value);
}

export function toStrings(value: unknown): string[] {
  return (Array.isArray(value) ? value : [])
    .map((entry) => String(entry))
    .filter((entry) => entry !== '');
}

function pick(values: string[], allowed: string[]): string[] | undefined {
  const kept = values.filter((value) => allowed.includes(value));
  return kept.length > 0 ? kept : undefined;
}

function validRange(
  from?: string,
  to?: string,
): { sent_at_from?: string; sent_at_to?: string } {
  const validFrom = from && isStrictDateString(from) ? from : undefined;
  const validTo = to && isStrictDateString(to) ? to : undefined;
  if (validFrom && validTo && validFrom > validTo) {
    return { sent_at_from: validTo, sent_at_to: validFrom };
  }
  return {
    ...(validFrom ? { sent_at_from: validFrom } : {}),
    ...(validTo ? { sent_at_to: validTo } : {}),
  };
}

export function viewFiltersToRequestFilter(
  filters: Filter[] | undefined,
  allowedStatuses: string[],
): NewslettersRequestFilter {
  const result: NewslettersRequestFilter = {};
  (filters ?? []).forEach((filter) => {
    if (filter.field === STATUS_FIELD) {
      const status = pick(toStrings(filter.value), allowedStatuses);
      if (status) result.status = status;
    } else if (filter.field === SEGMENTS_FIELD) {
      const ids = toStrings(filter.value).filter(isDigits);
      if (ids.length > 0) {
        result.segment_ids = ids;
        result.segment_operator =
          filter.operator === 'isNone' ? 'isNone' : 'isAny';
      }
    } else if (filter.field === SENT_AT_FIELD) {
      const { from, to } = dateRangeFromFilter(filter.operator, filter.value);
      Object.assign(result, validRange(from, to));
    }
  });
  return result;
}

function datesToFilter(from?: string, to?: string): Filter | null {
  if (from && to) {
    return from === to
      ? { field: SENT_AT_FIELD, operator: 'on', value: from }
      : { field: SENT_AT_FIELD, operator: 'between', value: [from, to] };
  }
  // Stored bounds are inclusive days, so seed the inclusive operators.
  if (from) return { field: SENT_AT_FIELD, operator: 'afterInc', value: from };
  if (to) return { field: SENT_AT_FIELD, operator: 'beforeInc', value: to };
  return null;
}

export function requestFilterToViewFilters(
  filter: NewslettersRequestFilter,
): Filter[] {
  const filters: Filter[] = [];
  if (filter.status && filter.status.length > 0) {
    filters.push({
      field: STATUS_FIELD,
      operator: 'isAny',
      value: filter.status,
    });
  }
  if (filter.segment_ids && filter.segment_ids.length > 0) {
    filters.push({
      field: SEGMENTS_FIELD,
      operator: filter.segment_operator === 'isNone' ? 'isNone' : 'isAny',
      value: filter.segment_ids,
    });
  }
  const dateFilter = datesToFilter(filter.sent_at_from, filter.sent_at_to);
  if (dateFilter) filters.push(dateFilter);
  return filters;
}

function splitList(value: string | undefined): string[] {
  return (value ?? '').split(',').filter((entry) => entry !== '');
}

// Invalid values are dropped silently: bookmarks outlive the data they point to.
export function hashFilterToRequestFilter(
  hash: Record<string, string>,
  allowedStatuses: string[],
): NewslettersRequestFilter {
  const result: NewslettersRequestFilter = {};
  const status = pick(splitList(hash.status), allowedStatuses);
  if (status) result.status = status;
  // A legacy `segment=3` is a one-item list and means "is any".
  const ids = splitList(hash.segment).filter(isDigits);
  if (ids.length > 0) {
    result.segment_ids = ids;
    result.segment_operator = hash.segment_op === 'isNone' ? 'isNone' : 'isAny';
  }
  return { ...result, ...validRange(hash.sent_from, hash.sent_to) };
}

export function requestFilterToHashFilter(
  filter: NewslettersRequestFilter,
): Record<string, string> {
  const hash: Record<string, string> = {};
  if (filter.status && filter.status.length > 0) {
    hash.status = filter.status.join(',');
  }
  if (filter.segment_ids && filter.segment_ids.length > 0) {
    hash.segment = filter.segment_ids.join(',');
    if (filter.segment_operator === 'isNone') hash.segment_op = 'isNone';
  }
  if (filter.sent_at_from) hash.sent_from = filter.sent_at_from;
  if (filter.sent_at_to) hash.sent_to = filter.sent_at_to;
  return hash;
}

export function sendingStatusViewFiltersToRequestFilter(
  filters: Filter[] | undefined,
): SendingStatusRequestFilter {
  const filter = (filters ?? []).find((item) => item.field === RESULT_FIELD);
  const result = filter ? pick(toStrings(filter.value), RESULT_VALUES) : null;
  return result ? { result } : {};
}

export function sendingStatusRequestFilterToViewFilters(
  filter: SendingStatusRequestFilter,
): Filter[] {
  return filter.result && filter.result.length > 0
    ? [{ field: RESULT_FIELD, operator: 'isAny', value: filter.result }]
    : [];
}

export function sendingStatusHashFilterToRequestFilter(
  hash: Record<string, string>,
  allowResult: boolean,
): SendingStatusRequestFilter {
  if (!allowResult) return {};
  const result = pick(splitList(hash.result), RESULT_VALUES);
  return result ? { result } : {};
}

export function sendingStatusRequestFilterToHashFilter(
  filter: SendingStatusRequestFilter,
): Record<string, string> {
  return filter.result && filter.result.length > 0
    ? { result: filter.result.join(',') }
    : {};
}

// Old links may sort by columns the tab no longer shows but the endpoint accepts.
const ENDPOINT_SORT_FIELDS = [
  'id',
  'name',
  'subject',
  'status',
  'sent_at',
  'updated_at',
  'created_at',
];

export function getAllowedSortIds(sortableFieldIds: string[]): string[] {
  return [...new Set([...sortableFieldIds, ...ENDPOINT_SORT_FIELDS])];
}

export function sanitizeSort(
  sort: SortValue | undefined,
  sortableFieldIds: string[],
  defaultSort: SortValue,
): SortValue {
  return sort && sortableFieldIds.includes(sort.field) ? sort : defaultSort;
}

export function withoutFields(
  filters: Filter[] | undefined,
  fieldIds: string[],
): Filter[] {
  return (filters ?? []).filter((filter) => !fieldIds.includes(filter.field));
}
