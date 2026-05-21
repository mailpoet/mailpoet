import {
  configureRestApi,
  createRestListingLoader,
  restPost,
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

// The sending status page lives inside the Emails SPA, so it shares the REST
// root + nonce already localized for the newsletters listing.
configureRestApi(window.mailpoet_newsletters_api);

const listingPath = (newsletterId: number): string =>
  `/mailpoet/v1/newsletters/${newsletterId}/sending-status`;
const resendPath = (newsletterId: number): string =>
  `/mailpoet/v1/newsletters/${newsletterId}/sending-status/resend`;

export type SendingStatusItem = {
  taskId: number;
  subscriberId: number;
  email: string;
  firstName: string;
  lastName: string;
  processed: number;
  failed: number;
  error: string | null;
};

// Mailer / cron envelope fields the endpoint appends to the listing response;
// the page feeds them to `checkMailerStatus` / `checkCronStatus`.
export type SendingStatusExtras = {
  mta_log: unknown;
  mta_method: string | null;
  cron_accessible: boolean | null;
  current_time: string;
};

export type SendingStatusListingParams = ListingQueryParams & {
  group?: string;
};

export type SendingStatusApiError = RestApiError;

export function getSendingStatusSubscribers(
  newsletterId: number,
  params: SendingStatusListingParams,
  signal?: AbortSignal,
): Promise<ListingResponse<SendingStatusItem> & Partial<SendingStatusExtras>> {
  const loader = createRestListingLoader<SendingStatusItem>(
    listingPath(newsletterId),
  );
  return loader(params, signal);
}

export function resendFailedEmail(
  newsletterId: number,
  taskId: number,
  subscriberId: number,
): Promise<unknown> {
  return restPost(resendPath(newsletterId), {
    task_id: taskId,
    subscriber_id: subscriberId,
  });
}
