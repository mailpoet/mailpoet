import type { View } from '@wordpress/dataviews';
import type { CustomFieldsRequestFilter } from './types';

export const DEFAULT_PAGE = 1;
export const DEFAULT_PER_PAGE = 20;
export const MAX_PER_PAGE = 100;

export type CustomFieldsGroup = 'all' | 'trash';

export type CustomFieldsUrlState = {
  page: number;
  perPage?: number;
  search?: string;
  group: CustomFieldsGroup;
  filter: CustomFieldsRequestFilter;
};

const ALLOWED_TYPES = [
  'text',
  'textarea',
  'radio',
  'checkbox',
  'select',
  'date',
];

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

function parseTypes(value: string | null): string[] {
  if (!value) return [];
  return value
    .split(',')
    .map((entry) => entry.trim())
    .filter((entry) => ALLOWED_TYPES.includes(entry));
}

export function parseCustomFieldsUrlState(url: string): CustomFieldsUrlState {
  const searchParams = new URL(url).searchParams;
  const search = searchParams.get('search')?.trim();
  const group = searchParams.get('group') === 'trash' ? 'trash' : 'all';

  const filter: CustomFieldsRequestFilter = {};
  const types = parseTypes(searchParams.get('type'));
  if (types.length > 0) {
    filter.type = types;
  }

  return {
    page: parsePositiveInt(searchParams.get('cf_page')) ?? DEFAULT_PAGE,
    perPage: clampPerPage(parsePositiveInt(searchParams.get('per_page'))),
    search: search || undefined,
    group,
    filter,
  };
}

export function buildCustomFieldsUrl(
  currentUrl: string,
  view: View,
  group: CustomFieldsGroup,
  filter: CustomFieldsRequestFilter,
): string {
  const url = new URL(currentUrl);
  const search = view.search?.trim();

  url.searchParams.set('page', 'mailpoet-custom-fields');
  url.searchParams.delete('search');
  url.searchParams.delete('group');
  url.searchParams.delete('type');

  if (search) {
    url.searchParams.set('search', search);
  }
  if (group === 'trash') {
    url.searchParams.set('group', 'trash');
  }
  if (filter.type && filter.type.length > 0) {
    url.searchParams.set('type', filter.type.join(','));
  }

  url.searchParams.set('cf_page', String(view.page ?? DEFAULT_PAGE));
  url.searchParams.set(
    'per_page',
    String(clampPerPage(view.perPage ?? DEFAULT_PER_PAGE) ?? DEFAULT_PER_PAGE),
  );

  return url.href;
}
