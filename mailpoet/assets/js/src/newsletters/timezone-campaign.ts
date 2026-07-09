import { __ } from '@wordpress/i18n';

export type TimezoneBreakdownEntry = {
  timezone: string | null;
  fallbackUsed: boolean;
  scheduledAt: string | null;
  status: string | null;
  countTotal: number;
  countProcessed: number;
  countToProcess: number;
};

export type ScheduledWindow = {
  first: string;
  last: string;
};

// The listing and stats responses type `queue` loosely and use `false` when
// a newsletter has no queue (legacy BC shape), so accept anything and narrow.
type QueueLike = { meta?: unknown } | false | null | undefined;

const getQueueMeta = (queue: QueueLike): Record<string, unknown> | null => {
  if (!queue || typeof queue !== 'object') {
    return null;
  }
  const { meta } = queue;
  if (!meta || typeof meta !== 'object' || Array.isArray(meta)) {
    return null;
  }
  return meta as Record<string, unknown>;
};

export const isTimezoneCampaignQueue = (queue: QueueLike): boolean =>
  Boolean(getQueueMeta(queue)?.sendByTimezone);

const toCount = (value: unknown): number => {
  const parsed = Number(value);
  return Number.isFinite(parsed) && parsed > 0 ? Math.floor(parsed) : 0;
};

const toStringOrNull = (value: unknown): string | null =>
  typeof value === 'string' && value !== '' ? value : null;

export const getTimezoneBreakdown = (
  queue: QueueLike,
): TimezoneBreakdownEntry[] => {
  const breakdown = getQueueMeta(queue)?.timezoneBreakdown;
  if (!Array.isArray(breakdown)) {
    return [];
  }
  return breakdown
    .filter(
      (entry): entry is Record<string, unknown> =>
        Boolean(entry) && typeof entry === 'object' && !Array.isArray(entry),
    )
    .map((entry) => ({
      timezone: toStringOrNull(entry.timezone),
      fallbackUsed: Boolean(entry.fallback_used),
      scheduledAt: toStringOrNull(entry.scheduled_at),
      status: toStringOrNull(entry.status),
      countTotal: toCount(entry.count_total),
      countProcessed: toCount(entry.count_processed),
      countToProcess: toCount(entry.count_to_process),
    }));
};

export const getScheduledWindow = (
  queue: QueueLike,
): ScheduledWindow | null => {
  const meta = getQueueMeta(queue);
  const first = toStringOrNull(meta?.firstScheduledAt);
  const last = toStringOrNull(meta?.lastScheduledAt);
  return first && last ? { first, last } : null;
};

// A null batch status is authoritative and means the batch is actively
// sending (the scheduler already picked its task up). It must not fall back
// to any other label — see the response builders, which deliberately avoid
// `??` for the same reason.
export const getBatchStatusLabel = (status: string | null): string => {
  if (status === null) {
    return __('Sending', 'mailpoet');
  }
  switch (status) {
    case 'scheduled':
      return __('Scheduled', 'mailpoet');
    case 'paused':
      return __('Paused', 'mailpoet');
    case 'completed':
      return __('Sent', 'mailpoet');
    case 'cancelled':
      return __('Cancelled', 'mailpoet');
    case 'invalid':
      return __('Failed', 'mailpoet');
    default:
      return status;
  }
};

export const formatTimezoneLabel = (
  timezone: string | null,
  fallbackUsed: boolean,
): string => {
  const name = timezone || __('Unknown', 'mailpoet');
  if (!fallbackUsed) {
    return name;
  }
  return (
    // translators: %1$s is a time zone name. The suffix marks subscribers without a time zone of their own, who receive the email in the site's default time zone.
    __('%1$s (site default)', 'mailpoet').replace('%1$s', name)
  );
};

// Client-side mirror of the backend canReplaceScheduledCampaign() guard: a
// batch is still replaceable while it is scheduled or paused with nothing
// processed. Anything else (running, completed, cancelled, invalid, or a
// batch with processed recipients) means the campaign can no longer be
// edited. The server remains authoritative; this only enables a clean
// message before the editor is opened.
export const hasStartedTimezoneBatches = (queue: QueueLike): boolean =>
  getTimezoneBreakdown(queue).some(
    (entry) =>
      entry.countProcessed > 0 ||
      entry.status === null ||
      !['scheduled', 'paused'].includes(entry.status),
  );
