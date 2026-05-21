import {
  configureRestApi,
  createRestListingLoader,
  restPost,
  restPut,
  type ListingQueryParams,
  type ListingResponse,
  type RestApiError,
} from 'common/dataviews';

declare global {
  interface Window {
    mailpoet_newsletters_api: {
      root: string;
      nonce: string;
    };
  }
}

configureRestApi(window.mailpoet_newsletters_api);

const LISTING_PATH = '/mailpoet/v1/newsletters';
const BULK_ACTION_PATH = '/mailpoet/v1/newsletters/bulk-action';
const DUPLICATE_PATH = (id: number): string =>
  `/mailpoet/v1/newsletters/${id}/duplicate`;
const STATUS_PATH = (id: number): string =>
  `/mailpoet/v1/newsletters/${id}/status`;

export type NewsletterStatus =
  | 'draft'
  | 'scheduled'
  | 'sending'
  | 'sent'
  | 'active'
  | 'corrupt';

export type NewsletterType =
  | 'standard'
  | 'notification'
  | 'notification_history'
  | 're_engagement'
  | 'welcome'
  | 'automatic';

export type NewsletterListingItem = {
  id: string;
  hash: string;
  subject: string;
  type: NewsletterType;
  status: NewsletterStatus;
  sent_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
  segments: Array<{ id: string | number; name: string; type: string }>;
  // `false` for back-compat with the legacy JSON shape; queue may be missing
  // entirely on email types that don't render this column.
  queue:
    | false
    | {
        status: string | null;
        scheduled_at: string | null;
        newsletter_rendered_subject: string | null;
        count_processed: string | number;
        count_to_process: string | number;
        count_failed: string | number;
      };
  options?: Record<string, unknown>;
  statistics?:
    | false
    | {
        clicked: string | number;
        opened: string | number;
        unsubscribed: string | number;
        revenue: { value: number; formatted: string; count: number } | null;
        children?: { clicked: number; opened: number; unsubscribed: number };
      };
  preview_url: string;
  total_sent?: number;
  total_scheduled?: number;
  children_count?: number;
  campaign_name?: string | null;
  wp_post_id?: number | null;
  share_url?: string;
  share_visibility?: 'public' | 'recipients' | string;
  effective_share_visibility?: 'public' | 'recipients' | string;
  can_share?: boolean;
  is_share_supported?: boolean;
  share_unavailable_reason?: string;
};

export type NewslettersListingMeta = {
  count: number;
  pages: number;
};

export type NewslettersListingExtras = {
  mta_log: unknown;
  mta_method: string | null;
  // `null` means the cron daemon status is not yet known; only an explicit
  // `false` should surface the "sending is broken" notice.
  cron_accessible: boolean | null;
  current_time: string;
};

export type NewslettersListingParams = ListingQueryParams & {
  type?: NewsletterType;
  parent_id?: number;
  filter?: Record<string, string>;
};

export type NewslettersBulkAction =
  | 'trash'
  | 'restore'
  | 'delete'
  | 'export_stats';

export type NewslettersBulkScope = {
  type: NewsletterType;
  group?: string;
  filter?: Record<string, string>;
  search?: string;
  selection?: number[];
  select_all?: boolean;
  parent_id?: number;
};

export type NewslettersBulkResult = {
  action: NewslettersBulkAction;
  count: number;
  task_id?: number;
};

export type NewsletterApiError = RestApiError;

/**
 * `useDataViewsQuery` exposes only `items + meta + filters + groups`, but the
 * newsletters listing additionally surfaces mailer / cron envelope info via
 * `meta` on the legacy endpoint. We expose it through a module-level
 * subscriber model so feature listings can read the latest values without
 * duplicating the loader.
 */
type ExtrasListener = (extras: NewslettersListingExtras) => void;
const extrasListeners = new Set<ExtrasListener>();
let lastExtras: NewslettersListingExtras | null = null;

export function onNewslettersListingExtras(
  listener: ExtrasListener,
): () => void {
  extrasListeners.add(listener);
  if (lastExtras) listener(lastExtras);
  return () => {
    extrasListeners.delete(listener);
  };
}

function publishExtras(extras: NewslettersListingExtras): void {
  lastExtras = extras;
  extrasListeners.forEach((listener) => listener(extras));
}

const baseLoader = createRestListingLoader<NewsletterListingItem>(LISTING_PATH);

export function getNewsletters(
  params: NewslettersListingParams,
  signal?: AbortSignal,
): Promise<ListingResponse<NewsletterListingItem>> {
  return baseLoader(params, signal).then((response) => {
    const extras = response as ListingResponse<NewsletterListingItem> &
      Partial<NewslettersListingExtras>;
    if (extras.mta_log !== undefined || extras.cron_accessible !== undefined) {
      publishExtras({
        mta_log: extras.mta_log ?? null,
        mta_method:
          typeof extras.mta_method === 'string' ? extras.mta_method : null,
        cron_accessible:
          typeof extras.cron_accessible === 'boolean'
            ? extras.cron_accessible
            : null,
        current_time:
          typeof extras.current_time === 'string' ? extras.current_time : '',
      });
    }
    return response;
  });
}

export function bulkAction(
  action: NewslettersBulkAction,
  scope: NewslettersBulkScope,
  extra: Record<string, unknown> = {},
): Promise<{ data: NewslettersBulkResult }> {
  return restPost<{ data: NewslettersBulkResult }>(BULK_ACTION_PATH, {
    action,
    type: scope.type,
    group: scope.group ?? 'all',
    search: scope.search ?? '',
    filter: scope.filter ?? {},
    selection: scope.select_all ? [] : scope.selection ?? [],
    select_all: Boolean(scope.select_all),
    parent_id: scope.parent_id,
    ...extra,
  });
}

export function duplicateNewsletter(
  id: number,
): Promise<{ data: NewsletterListingItem }> {
  return restPost<{ data: NewsletterListingItem }>(DUPLICATE_PATH(id), {});
}

export function setNewsletterStatus(
  id: number,
  status: NewsletterStatus | 'active' | 'draft',
): Promise<{ data: NewsletterListingItem }> {
  return restPut<{ data: NewsletterListingItem }>(STATUS_PATH(id), { status });
}
