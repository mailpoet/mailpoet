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
 * Shift a `yyyy-MM-dd` string by whole days, preserving the `yyyy-MM-dd` shape.
 */
function shiftYmd(ymd: string, deltaDays: number): string {
  const [year, month, day] = ymd.split('-').map(Number);
  const date = new Date(Date.UTC(year, month - 1, day + deltaDays));
  const shiftedMonth = String(date.getUTCMonth() + 1).padStart(2, '0');
  const shiftedDay = String(date.getUTCDate()).padStart(2, '0');
  return `${date.getUTCFullYear()}-${shiftedMonth}-${shiftedDay}`;
}

/**
 * Translate a single DataViews date filter into a `{ from, to }` range the
 * listing endpoints apply as inclusive whole days (`>= from 00:00:00`,
 * `<= to 23:59:59`). Supports the standard date operators (`on`,
 * `beforeInc`/`afterInc` inclusive, `before`/`after` exclusive, `between`).
 *
 * Because the backend boundaries are inclusive, the exclusive `before`/`after`
 * operators are shifted one day so they exclude the picked day, while the
 * inclusive `beforeInc`/`afterInc` map straight onto the boundary. Unrecognized
 * or invalid input yields an empty range.
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
  if (operator === 'beforeInc') {
    return { to: ymd };
  }
  if (operator === 'before') {
    return { to: shiftYmd(ymd, -1) };
  }
  if (operator === 'afterInc') {
    return { from: ymd };
  }
  if (operator === 'after') {
    return { from: shiftYmd(ymd, 1) };
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
