import { __ } from '@wordpress/i18n';
import type { View } from '@wordpress/dataviews';

export const DEFAULT_PAGE = 1;
export const DEFAULT_PER_PAGE = 20;
export const MAX_PER_PAGE = 100;

export type DateFilters = {
  from?: string;
  to?: string;
};

export type LogsUrlState = {
  page: number;
  perPage: number;
  search?: string;
  dateFilters: DateFilters;
};

function parsePositiveInt(value: string | null): number | undefined {
  if (!value) return undefined;

  const parsed = Number(value);
  if (!Number.isInteger(parsed) || parsed < 1) {
    return undefined;
  }

  return parsed;
}

function clampPerPage(perPage: number | undefined): number {
  if (!perPage) {
    return DEFAULT_PER_PAGE;
  }
  return Math.min(perPage, MAX_PER_PAGE);
}

function parseOffset(value: string | null): number | undefined {
  if (value === null || value.trim() === '') {
    return undefined;
  }

  const parsed = Number(value);
  if (!Number.isInteger(parsed) || parsed < 0) {
    return undefined;
  }

  return parsed;
}

function hasOnlyDigits(value: string): boolean {
  return value
    .split('')
    .every((character) => character >= '0' && character <= '9');
}

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

export function dateFromString(value: string | undefined): Date | undefined {
  if (!isStrictDateString(value)) {
    return undefined;
  }

  const [year, month, day] = value.split('-').map(Number);
  return new Date(year, month - 1, day);
}

function padDatePart(value: number): string {
  return String(value).padStart(2, '0');
}

export function formatDateAsYmd(date: Date | null): string | undefined {
  if (!date || Number.isNaN(date.getTime())) {
    return undefined;
  }

  return [
    date.getFullYear(),
    padDatePart(date.getMonth() + 1),
    padDatePart(date.getDate()),
  ].join('-');
}

function getSearch(searchParams: URLSearchParams): string | undefined {
  const search = searchParams.get('search')?.trim();
  return search || undefined;
}

function getDateFilters(
  searchParams: URLSearchParams,
  defaultFrom: string,
): DateFilters {
  const from = searchParams.get('from');
  const to = searchParams.get('to');
  const filters: DateFilters = {};

  if (isStrictDateString(from)) {
    filters.from = from;
  } else if (isStrictDateString(defaultFrom)) {
    filters.from = defaultFrom;
  }

  if (isStrictDateString(to)) {
    filters.to = to;
  }

  return filters;
}

function getPage(searchParams: URLSearchParams, perPage: number): number {
  const logsPage = parsePositiveInt(searchParams.get('logs_page'));
  if (logsPage) {
    return logsPage;
  }

  const offset = parseOffset(searchParams.get('offset'));
  if (offset !== undefined) {
    return Math.floor(offset / perPage) + 1;
  }

  return DEFAULT_PAGE;
}

function getPerPage(searchParams: URLSearchParams): number {
  const logsPerPage = parsePositiveInt(searchParams.get('per_page'));
  if (logsPerPage) {
    return clampPerPage(logsPerPage);
  }

  return clampPerPage(parsePositiveInt(searchParams.get('limit')));
}

export function parseLogsUrlState(
  url: string,
  defaultFrom: string,
): LogsUrlState {
  const searchParams = new URL(url).searchParams;
  const perPage = getPerPage(searchParams);

  return {
    page: getPage(searchParams, perPage),
    perPage,
    search: getSearch(searchParams),
    dateFilters: getDateFilters(searchParams, defaultFrom),
  };
}

export function buildLogsUrl(
  currentUrl: string,
  view: View,
  dateFilters: DateFilters,
): string {
  const url = new URL(currentUrl);
  const search = view.search?.trim();

  url.searchParams.set('page', 'mailpoet-logs');
  url.searchParams.delete('offset');
  url.searchParams.delete('limit');
  url.searchParams.delete('search');
  url.searchParams.delete('from');
  url.searchParams.delete('to');

  if (search) {
    url.searchParams.set('search', search);
  }
  if (dateFilters.from) {
    url.searchParams.set('from', dateFilters.from);
  }
  if (dateFilters.to) {
    url.searchParams.set('to', dateFilters.to);
  }

  url.searchParams.set('logs_page', String(view.page ?? DEFAULT_PAGE));
  url.searchParams.set(
    'per_page',
    String(clampPerPage(view.perPage ?? DEFAULT_PER_PAGE)),
  );

  return url.href;
}

export function getDateRangeError(dateFilters: DateFilters): string | null {
  // Keep in sync with LogsListingEndpoint::validateFilters; the browser blocks
  // invalid ranges, while REST validates direct requests.
  if (dateFilters.from && dateFilters.to && dateFilters.from > dateFilters.to) {
    return __(
      'The start date must be before or equal to the end date.',
      'mailpoet',
    );
  }

  return null;
}
