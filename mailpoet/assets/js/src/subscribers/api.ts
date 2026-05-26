import {
  configureRestApi,
  createRestListingLoader,
  restPost,
  type ListingQueryParams,
  type ListingResponse,
  type RestApiError,
} from 'common/dataviews';
import type { EngagementScoreType } from './engagement-score-badge-type';

declare global {
  interface Window {
    mailpoet_subscribers_api: {
      root: string;
      nonce: string;
    };
  }
}

configureRestApi(window.mailpoet_subscribers_api);

const LISTING_PATH = '/mailpoet/v1/subscribers';
const BULK_ACTION_PATH = '/mailpoet/v1/subscribers/bulk-action';
const RESEND_CONFIRMATION_PATH = (id: number): string =>
  `/mailpoet/v1/subscribers/${id}/resend-confirmation-email`;

export type Segment = {
  id: string;
  name: string;
  subscribers: string;
  type: 'default' | 'wp_users' | 'woocommerce_users' | 'dynamic';
  deleted_at?: string | null;
};

export type Subscriber = {
  id: number | string;
  wp_user_id: number;
  is_woocommerce_user: number;
  count_confirmations: number;
  status: string;
  email: string;
  first_name: string;
  last_name: string;
  engagement_score: number | null;
  engagement_score_type: EngagementScoreType;
  created_at: string | null;
  deleted_at: string | null;
  last_subscribed_at: string | null;
  subscriptions: Array<{
    id: number;
    status: string;
    segment_id: number | string;
  }>;
  tags: Array<{
    id: string;
    tag_id: string;
    subscriber_id: string;
    name: string;
  }>;
};

export type SubscriberBulkAction =
  | 'moveToList'
  | 'addToList'
  | 'removeFromList'
  | 'removeFromAllLists'
  | 'trash'
  | 'restore'
  | 'delete'
  | 'unsubscribe'
  | 'resendConfirmationEmails'
  | 'addTag'
  | 'removeTag';

export type SubscriberBulkActionScope = {
  group: string;
  filter: Record<string, string>;
  search: string;
  selection: number[];
};

export type SubscriberBulkActionResult = {
  action: SubscriberBulkAction;
  count: number;
  segment: { id: number; name: string } | null;
  tag: { id: number; name: string } | null;
  queue?: {
    selected_count: number;
    eligible_count: number;
    queued_count: number;
    skipped_count: number;
    skipped_by_reason: Record<string, number>;
    task_id: number | null;
    message: string;
  };
};

export type SubscriberListingParams = ListingQueryParams & {
  filter?: Record<string, string>;
};

export type SubscriberApiError = RestApiError;

const loadSubscribers = createRestListingLoader<Subscriber>(LISTING_PATH);

export function getSubscribers(
  params: SubscriberListingParams,
  signal?: AbortSignal,
): Promise<ListingResponse<Subscriber>> {
  return loadSubscribers(params, signal);
}

export function bulkAction(
  action: SubscriberBulkAction,
  scope: SubscriberBulkActionScope,
  extra: Record<string, unknown> = {},
): Promise<{ data: SubscriberBulkActionResult }> {
  return restPost<{ data: SubscriberBulkActionResult }>(BULK_ACTION_PATH, {
    action,
    selection: scope.selection,
    group: scope.group,
    search: scope.search,
    filter: scope.filter,
    ...extra,
  });
}

export async function sendConfirmationEmail(id: number): Promise<void> {
  await restPost<{ data: { sent: boolean } }>(RESEND_CONFIRMATION_PATH(id), {});
}
