import type { View } from '@wordpress/dataviews';
import { isStrictDateString } from 'common/dataviews';
import type { TagsRequestFilter } from './types';

export const DEFAULT_PAGE = 1;
export const DEFAULT_PER_PAGE = 20;
export const MAX_PER_PAGE = 100;

export type TagsUrlState = {
  page: number;
  perPage?: number;
  search?: string;
  filter: TagsRequestFilter;
};

function parsePositiveInt(value: string | null): number | undefined {
  if (!value) return undefined;
  const parsed = Number(value);
  if (!Number.isInteger(parsed) || parsed < 1) {
    return undefined;
  }
  return parsed;
}

function clampPerPage(perPage: number | undefined): number | undefined {
  if (!perPage) {
    return undefined;
  }
  return Math.min(perPage, MAX_PER_PAGE);
}

// Subscriber-count bucket values are the bucket's lower bound as a string
// (e.g. "0", "10", "10000"). The endpoint validates them against the live
// buckets, so here we only keep well-formed digit values.
function parseSubscribers(value: string | null): string[] {
  if (!value) {
    return [];
  }
  return value
    .split(',')
    .map((entry) => entry.trim())
    .filter(
      (entry) =>
        entry !== '' &&
        [...entry].every((character) => character >= '0' && character <= '9'),
    );
}

export function parseTagsUrlState(url: string): TagsUrlState {
  const searchParams = new URL(url).searchParams;
  const search = searchParams.get('search')?.trim();
  const from = searchParams.get('created_from');
  const to = searchParams.get('created_to');
  const subscribers = parseSubscribers(searchParams.get('subscribers'));

  const filter: TagsRequestFilter = {};
  if (isStrictDateString(from)) {
    filter.from = from;
  }
  if (isStrictDateString(to)) {
    filter.to = to;
  }
  if (subscribers.length > 0) {
    filter.subscribers = subscribers;
  }

  return {
    page: parsePositiveInt(searchParams.get('tags_page')) ?? DEFAULT_PAGE,
    perPage: clampPerPage(parsePositiveInt(searchParams.get('per_page'))),
    search: search || undefined,
    filter,
  };
}

export function buildTagsUrl(
  currentUrl: string,
  view: View,
  filter: TagsRequestFilter,
): string {
  const url = new URL(currentUrl);
  const search = view.search?.trim();

  url.searchParams.set('page', 'mailpoet-tags');
  url.searchParams.delete('search');
  url.searchParams.delete('created_from');
  url.searchParams.delete('created_to');
  url.searchParams.delete('subscribers');

  if (search) {
    url.searchParams.set('search', search);
  }
  if (filter.from) {
    url.searchParams.set('created_from', filter.from);
  }
  if (filter.to) {
    url.searchParams.set('created_to', filter.to);
  }
  if (filter.subscribers && filter.subscribers.length > 0) {
    url.searchParams.set('subscribers', filter.subscribers.join(','));
  }

  url.searchParams.set('tags_page', String(view.page ?? DEFAULT_PAGE));
  url.searchParams.set(
    'per_page',
    String(clampPerPage(view.perPage ?? DEFAULT_PER_PAGE) ?? DEFAULT_PER_PAGE),
  );

  return url.href;
}
