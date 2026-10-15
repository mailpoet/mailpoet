import type { Filter } from '@wordpress/dataviews';
import { dateRangeFromFilter, normalizeYmd, shiftYmd } from 'common/dataviews';
import { toFiniteNumber } from 'segments/static/filters';

export const STATUS_FIELD = 'status';
export const SEGMENTS_FIELD = 'segments';
export const TAGS_FIELD = 'tags';
export const SOURCE_FIELD = 'source';
export const SCORE_FIELD = 'statistics';
export const SUBSCRIBED_AT_FIELD = 'last_subscribed_at';
export const CREATED_AT_FIELD = 'created_at';
export const WITHOUT_LIST = 'without-list';

export type SubscribersRequestFilter = {
  segment_in?: Array<number | string>;
  segment_not_in?: Array<number | string>;
  tag_in?: number[];
  tag_not_in?: number[];
  status_in?: string[];
  status_not_in?: string[];
  source_in?: string[];
  source_not_in?: string[];
  score_gt?: number;
  score_lt?: number;
  score_min?: number;
  score_max?: number;
  created_from?: string;
  created_to?: string;
  subscribed_from?: string;
  subscribed_to?: string;
  segment?: number | string;
  tag?: number | string;
};

type Options = { trackingEnabled?: boolean };

const STATUSES = [
  'subscribed',
  'unconfirmed',
  'unsubscribed',
  'inactive',
  'bounced',
];
const SOURCES = [
  'form',
  'imported',
  'administrator',
  'api',
  'wordpress_user',
  'wordpress_user_deleted',
  'woocommerce_user',
  'woocommerce_checkout',
  'unknown',
];

export const SORT_FIELD_TO_ORDERBY: Record<string, string> = {
  [SCORE_FIELD]: 'engagement_score',
};

type SetKey =
  | 'segment_in'
  | 'segment_not_in'
  | 'tag_in'
  | 'tag_not_in'
  | 'status_in'
  | 'status_not_in'
  | 'source_in'
  | 'source_not_in';

const SET_FIELDS: Array<[string, string]> = [
  [STATUS_FIELD, 'status'],
  [SEGMENTS_FIELD, 'segment'],
  [TAGS_FIELD, 'tag'],
  [SOURCE_FIELD, 'source'],
];

const MAX_SET_VALUES = 100;

function sortedRange(
  low: number | string | undefined,
  high: number | string | undefined,
): [number | string | undefined, number | string | undefined] {
  return low !== undefined && high !== undefined && low > high
    ? [high, low]
    : [low, high];
}

function toPositiveInt(value: unknown): number | undefined {
  const text = String(value ?? '').trim();
  if (text === '' || !text.split('').every((c) => c >= '0' && c <= '9')) {
    return undefined;
  }
  const parsed = Number(text);
  return Number.isSafeInteger(parsed) && parsed > 0 ? parsed : undefined;
}

function cleanValues(prefix: string, value: unknown): Array<number | string> {
  const values = Array.isArray(value) ? value : [value];
  const result: Array<number | string> = [];
  values.forEach((entry) => {
    let clean: number | string | undefined;
    if (prefix === 'segment' && entry === WITHOUT_LIST) {
      clean = WITHOUT_LIST;
    } else if (prefix === 'segment' || prefix === 'tag') {
      clean = toPositiveInt(entry);
    } else {
      const text = String(entry ?? '');
      const allowed = prefix === 'status' ? STATUSES : SOURCES;
      clean = allowed.includes(text) ? text : undefined;
    }
    if (clean !== undefined && !result.includes(clean)) {
      result.push(clean);
    }
  });
  return result;
}

/**
 * Validate a raw filter object (URL or REST shape): unknown keys, invalid ids,
 * statuses, sources, numbers and dates are dropped and empty sets omitted. The
 * legacy `segment` / `tag` keys are folded into `segment_in` / `tag_in`.
 */
export function normalizeRequestFilter(
  raw: Record<string, unknown>,
): SubscribersRequestFilter {
  const result: Record<string, unknown> = {};
  SET_FIELDS.forEach(([, prefix]) => {
    ['in', 'not_in'].forEach((suffix) => {
      const key = `${prefix}_${suffix}`;
      // Legacy `segment` / `tag` (single id) mean "is any of".
      const legacy = prefix === 'segment' || prefix === 'tag';
      const input =
        raw[key] === undefined && suffix === 'in' && legacy
          ? raw[prefix]
          : raw[key];
      const values = cleanValues(prefix, input);
      if (values.length > 0) {
        result[key] = values.slice(0, MAX_SET_VALUES);
      }
    });
  });
  ['score_gt', 'score_lt', 'score_min', 'score_max'].forEach((key) => {
    const num = toFiniteNumber(raw[key]);
    if (num !== undefined) {
      result[key] = num;
    }
  });
  ['created_from', 'created_to', 'subscribed_from', 'subscribed_to'].forEach(
    (key) => {
      const ymd = normalizeYmd(raw[key]);
      if (ymd) {
        result[key] = ymd;
      }
    },
  );
  // The server rejects reversed ranges, so swap them to keep the user's intent.
  [
    ['score_min', 'score_max'],
    ['created_from', 'created_to'],
    ['subscribed_from', 'subscribed_to'],
  ].forEach(([lowKey, highKey]) => {
    const [low, high] = sortedRange(
      result[lowKey] as number | string | undefined,
      result[highKey] as number | string | undefined,
    );
    if (low !== undefined) {
      result[lowKey] = low;
    }
    if (high !== undefined) {
      result[highKey] = high;
    }
  });
  return result as SubscribersRequestFilter;
}

function scoreKeys(operator: string, value: unknown): SubscribersRequestFilter {
  if (operator === 'between') {
    const range = Array.isArray(value) ? value : [];
    const min = toFiniteNumber(range[0]);
    const max = toFiniteNumber(range[1]);
    return {
      ...(min !== undefined ? { score_min: min } : {}),
      ...(max !== undefined ? { score_max: max } : {}),
    };
  }
  const num = toFiniteNumber(value);
  if (num === undefined) {
    return {};
  }
  if (operator === 'greaterThan') {
    return { score_gt: num };
  }
  if (operator === 'lessThan') {
    return { score_lt: num };
  }
  return {};
}

export function viewFiltersToRequestFilter(
  filters: Filter[] | undefined,
  options: Options = {},
): SubscribersRequestFilter {
  const result: Record<string, unknown> = {};

  (filters ?? []).forEach((filter) => {
    const setPrefix = SET_FIELDS.find(([field]) => field === filter.field)?.[1];
    if (setPrefix) {
      const suffix = filter.operator === 'isNone' ? 'not_in' : 'in';
      if (filter.operator !== 'isAny' && filter.operator !== 'isNone') {
        return;
      }
      const values = cleanValues(setPrefix, filter.value);
      if (values.length > 0) {
        result[`${setPrefix}_${suffix}`] = values;
      }
    } else if (filter.field === SCORE_FIELD) {
      if (options.trackingEnabled !== false) {
        Object.assign(result, scoreKeys(filter.operator, filter.value));
      }
    } else if (
      filter.field === CREATED_AT_FIELD ||
      filter.field === SUBSCRIBED_AT_FIELD
    ) {
      const prefix =
        filter.field === CREATED_AT_FIELD ? 'created' : 'subscribed';
      const { from, to } = dateRangeFromFilter(filter.operator, filter.value);
      if (from) {
        result[`${prefix}_from`] = from;
      }
      if (to) {
        result[`${prefix}_to`] = to;
      }
    }
  });

  return normalizeRequestFilter(result);
}

function datesToFilter(
  field: string,
  from?: string,
  to?: string,
): Filter | null {
  if (from && to) {
    return from === to
      ? { field, operator: 'on', value: from }
      : { field, operator: 'between', value: [from, to] };
  }
  // The date fields offer only the exclusive `after`/`before`, so undo the
  // one-day shift that `dateRangeFromFilter` applies to them.
  if (from) {
    return { field, operator: 'after', value: shiftYmd(from, -1) };
  }
  if (to) {
    return { field, operator: 'before', value: shiftYmd(to, 1) };
  }
  return null;
}

// A lone bound becomes a half-filled "between": DataViews stores the empty
// side as an empty string.
function scoreToFilter(filter: SubscribersRequestFilter): Filter | null {
  const { score_min: min, score_max: max, score_gt: gt, score_lt: lt } = filter;
  if (min !== undefined || max !== undefined) {
    return {
      field: SCORE_FIELD,
      operator: 'between',
      value: [min ?? '', max ?? ''],
    };
  }
  if (gt !== undefined) {
    return { field: SCORE_FIELD, operator: 'greaterThan', value: gt };
  }
  if (lt !== undefined) {
    return { field: SCORE_FIELD, operator: 'lessThan', value: lt };
  }
  return null;
}

export function requestFilterToViewFilters(
  filter: Record<string, unknown>,
  options: Options = {},
): Filter[] {
  const clean = normalizeRequestFilter(filter);
  const filters: Filter[] = [];

  SET_FIELDS.forEach(([field, prefix]) => {
    (['in', 'not_in'] as const).forEach((suffix) => {
      const values = clean[`${prefix}_${suffix}` as SetKey];
      if (values && values.length > 0) {
        filters.push({
          field,
          operator: suffix === 'in' ? 'isAny' : 'isNone',
          value: values.map(String),
        });
      }
    });
  });

  const score = options.trackingEnabled === false ? null : scoreToFilter(clean);
  const subscribed = datesToFilter(
    SUBSCRIBED_AT_FIELD,
    clean.subscribed_from,
    clean.subscribed_to,
  );
  const created = datesToFilter(
    CREATED_AT_FIELD,
    clean.created_from,
    clean.created_to,
  );
  [score, subscribed, created].forEach((entry) => {
    if (entry) {
      filters.push(entry);
    }
  });

  return filters;
}

/**
 * The status when the status filter is "is any of" exactly one value.
 */
export function getExactStatus(filters: Filter[] | undefined): string | null {
  const status = (filters ?? []).find(
    (filter) => filter.field === STATUS_FIELD && filter.operator === 'isAny',
  );
  const values = Array.isArray(status?.value) ? status.value : [];
  return values.length === 1 ? String(values[0]) : null;
}

export function toOrderby(field: string): string {
  return SORT_FIELD_TO_ORDERBY[field] ?? field;
}

export function fromOrderby(orderby: string): string {
  const entry = Object.entries(SORT_FIELD_TO_ORDERBY).find(
    ([, value]) => value === orderby,
  );
  return entry ? entry[0] : orderby;
}

export function hasActiveFilters(
  filters: Filter[] | undefined,
  search: string | undefined,
): boolean {
  return (filters ?? []).length > 0 || !!search;
}
