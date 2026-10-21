/**
 * URL hash <-> listing state for the Subscribers listing.
 *
 * Grammar: `#/group[all]/filter[key=a,b&key2=c]/search[..]/page[2]/limit[50]/
 * sort_by[email]/sort_order[asc]`. Filter values are comma-joined and each
 * value is URI-encoded, so `,` `/` `[` `]` `&` `=` inside values cannot break
 * the grammar. Legacy links (`filter[segment=ID]`, `filter[tag=ID]`,
 * `group[<status>]`) are still understood. Pure, so it can be unit-tested.
 */
import {
  SUBSCRIBER_STATUSES,
  fromOrderby,
  normalizeRequestFilter,
  toOrderby,
  type SubscribersRequestFilter,
} from './filters';

export type SubscribersHashState = {
  group: 'all' | 'trash';
  page?: number;
  perPage?: number;
  order?: 'asc' | 'desc';
  orderby?: string;
  search?: string;
  filter: SubscribersRequestFilter;
};

export type SubscribersHashDefaults = {
  orderby: string;
  order: 'asc' | 'desc';
  perPage: number;
};

const FILTER_KEYS: Array<keyof SubscribersRequestFilter> = [
  'status_in',
  'status_not_in',
  'segment_in',
  'segment_not_in',
  'tag_in',
  'tag_not_in',
  'source_in',
  'source_not_in',
  'score_gt',
  'score_lt',
  'score_min',
  'score_max',
  'created_from',
  'created_to',
  'subscribed_from',
  'subscribed_to',
];

function safeDecode(value: string): string {
  try {
    return decodeURIComponent(value);
  } catch {
    return value;
  }
}

function isSetKey(key: string): boolean {
  return (
    key.endsWith('_in') ||
    key.endsWith('_not_in') ||
    key === 'segment' ||
    key === 'tag'
  );
}

function parseFilter(value: string): SubscribersRequestFilter {
  const raw: Record<string, unknown> = {};
  value.split('&').forEach((pair) => {
    const separator = pair.indexOf('=');
    if (separator < 1) {
      return;
    }
    const key = pair.slice(0, separator);
    const text = pair.slice(separator + 1);
    raw[key] = isSetKey(key)
      ? text.split(',').map(safeDecode)
      : safeDecode(text);
  });
  return normalizeRequestFilter(raw);
}

export function parseSubscribersHash(hash: string): SubscribersHashState {
  const state: SubscribersHashState = { group: 'all', filter: {} };
  let legacyStatus: string | undefined;

  hash.split('/').forEach((part) => {
    const trimmed = part.endsWith(']') ? part.slice(0, -1) : part;
    const open = trimmed.indexOf('[');
    if (open < 1) {
      return;
    }
    const key = trimmed.slice(0, open);
    const value = trimmed.slice(open + 1);
    if (!value) {
      return;
    }
    if (key === 'group') {
      if (value === 'trash') {
        state.group = 'trash';
      } else if (SUBSCRIBER_STATUSES.includes(value)) {
        legacyStatus = value;
      }
    } else if ((key === 'page' || key === 'paged') && Number(value) > 0) {
      state.page = Number(value);
    } else if ((key === 'per_page' || key === 'limit') && Number(value) > 0) {
      state.perPage = Number(value);
    } else if (key === 'sort_by' || key === 'orderby') {
      state.orderby = fromOrderby(value);
    } else if (
      (key === 'sort_order' || key === 'order') &&
      (value === 'asc' || value === 'desc')
    ) {
      state.order = value;
    } else if (key === 'search') {
      state.search = safeDecode(value);
    } else if (key === 'filter') {
      state.filter = parseFilter(value);
    }
  });

  if (legacyStatus && !state.filter.status_in) {
    state.filter = { ...state.filter, status_in: [legacyStatus] };
  }
  return state;
}

export function buildSubscribersHash(
  state: SubscribersHashState,
  defaults: SubscribersHashDefaults,
): string {
  const filterValue = FILTER_KEYS.filter(
    (key) => state.filter[key] !== undefined,
  )
    .map((key) => {
      const value = state.filter[key];
      const encoded = (Array.isArray(value) ? value : [value])
        .map((entry) => encodeURIComponent(String(entry)))
        .join(',');
      return `${key}=${encoded}`;
    })
    .join('&');

  const entries: Array<[string, string | number | undefined]> = [
    ['group', state.group],
    ['filter', filterValue || undefined],
    ['search', state.search ? encodeURIComponent(state.search) : undefined],
    ['page', state.page && state.page !== 1 ? state.page : undefined],
    [
      'limit',
      state.perPage && state.perPage !== defaults.perPage
        ? state.perPage
        : undefined,
    ],
    [
      'sort_by',
      state.orderby && state.orderby !== defaults.orderby
        ? toOrderby(state.orderby)
        : undefined,
    ],
    [
      'sort_order',
      state.order && state.order !== defaults.order ? state.order : undefined,
    ],
  ];
  const path = entries.reduce(
    (hash, [key, value]) => (value ? `${hash}/${key}[${value}]` : hash),
    '',
  );
  return `#${path || '/'}`;
}
