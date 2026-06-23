import type { Filter } from '@wordpress/dataviews';
import { dateRangeFromFilter } from 'common/dataviews';

export const CREATED_AT_FIELD = 'created_at';
export const SCORE_FIELD = 'average_engagement_score';

export type SegmentsRequestFilter = {
  created_from?: string;
  created_to?: string;
  score_min?: number;
  score_max?: number;
};

/**
 * Coerce a score-control value to a finite number, rejecting empty strings and
 * null (which `Number()` would silently turn into 0 and activate an unintended
 * filter). Returns undefined for anything that isn't a real number.
 */
function toFiniteNumber(value: unknown): number | undefined {
  if (typeof value === 'number') {
    return Number.isFinite(value) ? value : undefined;
  }
  if (typeof value === 'string') {
    const trimmed = value.trim();
    if (trimmed === '') return undefined;
    const parsed = Number(trimmed);
    return Number.isFinite(parsed) ? parsed : undefined;
  }
  return undefined;
}

/**
 * Translate the engagement-score numeric filter into `{ score_min, score_max }`.
 * `between` yields both bounds; `greaterThan`/`lessThan` yield a single bound.
 * Non-numeric input is dropped so a half-configured control sends nothing.
 */
function scoreKeys(operator: string, value: unknown): SegmentsRequestFilter {
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
    return { score_min: num };
  }
  if (operator === 'lessThan') {
    return { score_max: num };
  }
  return {};
}

/**
 * Translate DataViews native filters into the REST `filter` object the segments
 * (lists) listing endpoint understands (`{ created_from, created_to, score_min,
 * score_max }`). Empty filters are omitted so an inactive control never sends a
 * key.
 */
export function viewFiltersToRequestFilter(
  filters: Filter[] | undefined,
): SegmentsRequestFilter {
  const result: SegmentsRequestFilter = {};

  (filters ?? []).forEach((filter) => {
    if (filter.field === CREATED_AT_FIELD) {
      const { from, to } = dateRangeFromFilter(filter.operator, filter.value);
      if (from) result.created_from = from;
      if (to) result.created_to = to;
    } else if (filter.field === SCORE_FIELD) {
      Object.assign(result, scoreKeys(filter.operator, filter.value));
    }
  });

  return result;
}
