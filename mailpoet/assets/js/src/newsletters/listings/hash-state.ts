/**
 * URL hash <-> listing view state for the Emails listing.
 *
 * The newsletter listing has historically used the hash form
 * `#/<base>/group[trash]/page[2]/sort_by[updated_at]/...`. These helpers keep
 * that grammar so deep links continue to work after the DataViews migration.
 * They are pure (no `window` access) so the round-trip can be unit-tested.
 */

export type RouteHashState = Partial<{
  group: string;
  page: number;
  perPage: number;
  orderby: string;
  order: 'asc' | 'desc';
  search: string;
  filter: Record<string, string>;
}>;

// Only the view fields the hash encodes — kept structural so the module has no
// `@wordpress/dataviews` dependency and a full `View` is still assignable.
export type HashView = {
  search?: string;
  page?: number;
  perPage?: number;
  sort?: { field?: string; direction?: 'asc' | 'desc' };
};

export function parseFilter(value: string): Record<string, string> {
  const parsed = new URLSearchParams(value);
  return Array.from(parsed.entries()).reduce<Record<string, string>>(
    (filters, [key, filterValue]) =>
      filterValue ? { ...filters, [key]: filterValue } : filters,
    {},
  );
}

function safeDecode(value: string): string {
  try {
    return decodeURIComponent(value);
  } catch {
    return value;
  }
}

export function parseHash(
  hash: string,
  baseUrl: string,
  supportedGroups: string[],
): RouteHashState {
  const prefix = `#/${baseUrl}`;
  if (!hash.startsWith(prefix)) return {};
  const tail = hash.slice(prefix.length).replace(/^\//, '');
  return tail
    .split('/')
    .map((part) => (part.endsWith(']') ? part.slice(0, -1) : part).split('['))
    .reduce<RouteHashState>((params, [key, value]) => {
      if (!value) return params;
      if (key === 'group' && supportedGroups.includes(value)) {
        return { ...params, group: value };
      }
      if ((key === 'page' || key === 'paged') && Number(value) > 0) {
        return { ...params, page: Number(value) };
      }
      if ((key === 'per_page' || key === 'limit') && Number(value) > 0) {
        return { ...params, perPage: Number(value) };
      }
      if (key === 'sort_by' || key === 'orderby') {
        return { ...params, orderby: value };
      }
      if (
        (key === 'sort_order' || key === 'order') &&
        (value === 'asc' || value === 'desc')
      ) {
        return { ...params, order: value };
      }
      if (key === 'search') {
        return { ...params, search: safeDecode(value) };
      }
      if (key === 'filter') {
        return { ...params, filter: parseFilter(value) };
      }
      return params;
    }, {});
}

export function buildHash(
  baseUrl: string,
  group: string,
  view: HashView,
  filter: Record<string, string>,
  defaults: { sort: string; order: 'asc' | 'desc'; perPage: number },
): string {
  const filterValue = new URLSearchParams(filter).toString();
  const entries: Array<[string, string | number | undefined]> = [
    ['group', group !== 'all' ? group : undefined],
    ['filter', filterValue || undefined],
    ['search', view.search ? encodeURIComponent(view.search) : undefined],
    ['page', view.page && view.page !== 1 ? view.page : undefined],
    [
      'limit',
      view.perPage && view.perPage !== defaults.perPage
        ? view.perPage
        : undefined,
    ],
    [
      'sort_by',
      view.sort?.field && view.sort.field !== defaults.sort
        ? view.sort.field
        : undefined,
    ],
    [
      'sort_order',
      view.sort?.direction && view.sort.direction !== defaults.order
        ? view.sort.direction
        : undefined,
    ],
  ];
  const path = entries.reduce(
    (hash, [key, value]) => (value ? `${hash}/${key}[${value}]` : hash),
    '',
  );
  return `#/${baseUrl}${path}`;
}
