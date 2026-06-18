import { __ } from '@wordpress/i18n';
import type { Filter } from '@wordpress/dataviews';
import { isStrictDateString, type LogsFilter } from './url-state';

export const CREATED_AT_FIELD = 'created_at';
export const NAME_FIELD = 'name';
export const LEVEL_FIELD = 'level';

// Monolog severity levels mapped to human labels. Keep in sync with the
// integer levels stored on the `level` column (see MailPoet\Logging\LogHandler).
export const LOG_SEVERITY_LABELS: Record<number, string> = {
  100: __('Debug', 'mailpoet'),
  200: __('Info', 'mailpoet'),
  250: __('Notice', 'mailpoet'),
  300: __('Warning', 'mailpoet'),
  400: __('Error', 'mailpoet'),
  500: __('Critical', 'mailpoet'),
  550: __('Alert', 'mailpoet'),
  600: __('Emergency', 'mailpoet'),
};

export function getLogSeverityLabel(level: number): string {
  return LOG_SEVERITY_LABELS[level] ?? String(level);
}

// Severity is a finite Monolog enum, so the filter offers every level rather
// than only the ones already present in storage (no DB query needed).
export function getLogSeverityElements(): { value: number; label: string }[] {
  return Object.keys(LOG_SEVERITY_LABELS).map((level) => ({
    value: Number(level),
    label: LOG_SEVERITY_LABELS[Number(level)],
  }));
}

export type LogFilterOptions = {
  names: string[];
};

declare global {
  interface Window {
    mailpoet_logs_filter_options?: {
      names?: string[];
    };
  }
}

export function getLogFilterOptions(): LogFilterOptions {
  const options = window.mailpoet_logs_filter_options;
  return {
    names: Array.isArray(options?.names) ? options.names : [],
  };
}

function normalizeYmd(value: unknown): string | undefined {
  if (typeof value !== 'string') {
    return undefined;
  }
  // The DataViews date control emits `yyyy-MM-dd`; defensively trim any time
  // portion a bookmarked value might carry.
  const candidate = value.slice(0, 10);
  return isStrictDateString(candidate) ? candidate : undefined;
}

function dateFilterToRange(operator: string, value: unknown): LogsFilter {
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

function toStringArray(value: unknown): string[] {
  if (!Array.isArray(value)) {
    return [];
  }
  return value
    .map((entry) => String(entry).trim())
    .filter((entry) => entry !== '');
}

function toNumberArray(value: unknown): number[] {
  if (!Array.isArray(value)) {
    return [];
  }
  return value
    .map((entry) => Number(entry))
    .filter((entry) => Number.isInteger(entry));
}

/**
 * Translate DataViews native filters into the REST `filter` object the logs
 * endpoint understands (`{ from, to, name, level }`). The output doubles as the
 * persisted URL/query-string shape (see buildLogsUrl).
 */
export function viewFiltersToRequestFilter(
  filters: Filter[] | undefined,
): LogsFilter {
  const result: LogsFilter = {};

  (filters ?? []).forEach((filter) => {
    if (filter.field === CREATED_AT_FIELD) {
      Object.assign(result, dateFilterToRange(filter.operator, filter.value));
    } else if (filter.field === NAME_FIELD) {
      const names = toStringArray(filter.value);
      if (names.length > 0) {
        result.name = names;
      }
    } else if (filter.field === LEVEL_FIELD) {
      const levels = toNumberArray(filter.value);
      if (levels.length > 0) {
        result.level = levels;
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
  if (from) {
    return { field: CREATED_AT_FIELD, operator: 'after', value: from };
  }
  if (to) {
    return { field: CREATED_AT_FIELD, operator: 'before', value: to };
  }
  return null;
}

/**
 * Seed DataViews native filters from a parsed (legacy or current) URL filter
 * state. Maps a from+to range to a single `between` filter so it stays editable
 * as one native control.
 */
export function requestFilterToViewFilters(filter: LogsFilter): Filter[] {
  const filters: Filter[] = [];

  const dateFilter = datesToFilter(filter.from, filter.to);
  if (dateFilter) {
    filters.push(dateFilter);
  }
  if (filter.name && filter.name.length > 0) {
    filters.push({ field: NAME_FIELD, operator: 'isAny', value: filter.name });
  }
  if (filter.level && filter.level.length > 0) {
    filters.push({
      field: LEVEL_FIELD,
      operator: 'isAny',
      value: filter.level,
    });
  }

  return filters;
}
