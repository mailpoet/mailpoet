import type { Filter } from '@wordpress/dataviews';

/**
 * Shared helpers for translating `@wordpress/dataviews` native filters into the
 * REST query shape MailPoet listing endpoints expect. Every DataViews-backed
 * listing should reuse these so date handling stays identical across listings.
 */

function hasOnlyDigits(value: string): boolean {
  return value
    .split('')
    .every((character) => character >= '0' && character <= '9');
}

/**
 * True only for a calendar-valid `yyyy-MM-dd` string (rejects e.g. `2025-02-30`).
 */
export function isStrictDateString(value: string | undefined | null): boolean {
  if (!value || value.length !== 10) {
    return false;
  }

  const parts = value.split('-');
  if (
    parts.length !== 3 ||
    parts[0].length !== 4 ||
    parts[1].length !== 2 ||
    parts[2].length !== 2 ||
    !parts.every(hasOnlyDigits)
  ) {
    return false;
  }

  const year = Number(parts[0]);
  const month = Number(parts[1]);
  const day = Number(parts[2]);
  const date = new Date(Date.UTC(year, month - 1, day));

  return (
    date.getUTCFullYear() === year &&
    date.getUTCMonth() === month - 1 &&
    date.getUTCDate() === day
  );
}

/**
 * Coerce a DataViews date-control value to a valid `yyyy-MM-dd` string, or
 * undefined if it isn't one. The control emits `yyyy-MM-dd`; we defensively trim
 * any time portion a bookmarked value might carry.
 */
export function normalizeYmd(value: unknown): string | undefined {
  if (typeof value !== 'string') {
    return undefined;
  }
  const candidate = value.slice(0, 10);
  return isStrictDateString(candidate) ? candidate : undefined;
}

/**
 * Translate a single DataViews date filter into a `{ from, to }` range. Supports
 * the standard date operators (`on`, `before`/`beforeInc`, `after`/`afterInc`,
 * `between`). Unrecognized or invalid input yields an empty range.
 */
export function dateRangeFromFilter(
  operator: string,
  value: unknown,
): { from?: string; to?: string } {
  if (operator === 'between') {
    const range = Array.isArray(value) ? value : [];
    const from = normalizeYmd(range[0]);
    const to = normalizeYmd(range[1]);
    return { ...(from ? { from } : {}), ...(to ? { to } : {}) };
  }

  const ymd = normalizeYmd(value);
  if (!ymd) {
    return {};
  }
  if (operator === 'before' || operator === 'beforeInc') {
    return { to: ymd };
  }
  if (operator === 'after' || operator === 'afterInc') {
    return { from: ymd };
  }
  if (operator === 'on') {
    return { from: ymd, to: ymd };
  }
  return {};
}

/**
 * Build the `{ filter }` object for {@link useDataViewsQuery}'s `extraParams`
 * from an already-serialized request filter, omitting it entirely when empty so
 * an inactive listing never sends a `filter` param.
 */
export function filterToExtraParams(filter: Record<string, unknown>): {
  filter?: Record<string, unknown>;
} {
  return Object.keys(filter).length > 0 ? { filter } : {};
}

export type FilterList = Filter[] | undefined;
