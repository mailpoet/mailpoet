import { __ } from '@wordpress/i18n';
import type { Filter } from '@wordpress/dataviews';
import { dateRangeFromFilter } from 'common/dataviews';
import type { TagVariant } from '../common/tag/tag';
import type { LogsFilter } from './url-state';

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

// Plain English on purpose (logs are not translated). Generic, not claims
// about sending.
export const LOG_SEVERITY_DESCRIPTIONS: Record<number, string> = {
  100: 'Details for developers',
  200: 'Routine activity',
  250: 'Notable event',
  300: 'Something unexpected that MailPoet handled',
  400: 'Something failed',
  500: 'A serious problem',
  550: 'A serious problem',
  600: 'A serious problem',
};

export function getLogSeverityDescription(level: number): string {
  return LOG_SEVERITY_DESCRIPTIONS[level] ?? '';
}

export function getLogSeverityVariant(level: number): TagVariant {
  if (level >= 400) {
    return 'critical';
  }
  return level === 300 ? 'average' : 'unknown';
}

// Severity is a finite Monolog enum, so the filter offers every level rather
// than only the ones already present in storage (no DB query needed).
export function getLogSeverityElements(): { value: number; label: string }[] {
  return Object.keys(LOG_SEVERITY_LABELS).map((level) => ({
    value: Number(level),
    label: LOG_SEVERITY_LABELS[Number(level)],
  }));
}

// Log names are plain English on purpose (logs are not translated). Unknown
// names fall back to the raw name. Keep in sync with LoggerFactory::TOPIC_*.
export const LOG_TOPIC_LABELS: Record<string, string> = {
  newsletters: 'Newsletters',
  'post-notifications': 'Post notifications',
  mss: 'MailPoet Sending Service',
  premium: 'Premium key',
  'bridge-api': 'MailPoet account API',
  sending: 'Sending',
  cron: 'Background tasks',
  api: 'Admin API',
  tracking: 'Tracking',
  coupons: 'Coupons',
  provisioning: 'WordPress.com provisioning',
  segments: 'Lists and segments',
  'email-editor': 'Email editor',
  migrations: 'Migrations',
  pages: 'MailPoet pages',
  captcha: 'CAPTCHA',
  woocommerce_first_purchase: 'WooCommerce: first purchase',
  woocommerce_product_purchased_in_category:
    'WooCommerce: purchased in category',
  woocommerce_product_purchased: 'WooCommerce: purchased product',
  MailPoet: 'MailPoet (general)',
};

export function getLogTopicLabel(name: string): string {
  return Object.prototype.hasOwnProperty.call(LOG_TOPIC_LABELS, name)
    ? LOG_TOPIC_LABELS[name]
    : name;
}

// Values stay the raw names (they go into the URL and the REST filter).
export function getLogTopicElements(
  names: string[],
): { value: string; label: string }[] {
  return names.map((name) => {
    const label = getLogTopicLabel(name);
    return { value: name, label: label === name ? name : `${label} (${name})` };
  });
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
      Object.assign(result, dateRangeFromFilter(filter.operator, filter.value));
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
