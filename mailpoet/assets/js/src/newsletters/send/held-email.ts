import { isHeldQueue } from 'newsletters/listings/queue-labels';
import { SCHEDULE_MODE_SUBSCRIBER_TIMEZONE } from 'common/newsletter-schedule-mode';

type SchedulingOptions = {
  isScheduled?: string;
  scheduledAt?: string;
  scheduleMode?: string;
  scheduledLocalDate?: string;
  scheduledLocalTime?: string;
};

// A held email is paused by MailPoet, not by the user, so the send page treats
// it like any other email that can be sent or scheduled.
export function isPausedOnSendPage(
  status: string,
  queue?: { status?: string | null; pause_reason?: string | null },
): boolean {
  return (
    status === 'sending' &&
    !!queue &&
    queue.status === 'paused' &&
    !isHeldQueue(queue)
  );
}

type ScheduleDefaults = {
  scheduledAt: string;
  scheduledLocalDate: string;
  scheduledLocalTime: string;
};

// Rescheduling a held email opens the page with Schedule on. The server keeps
// options that are missing from a save, so the old date is replaced with the
// default one.
export function withoutStaleSchedule<T extends SchedulingOptions>(
  options: T,
  defaults: ScheduleDefaults,
): T {
  const isTimeZoneMode =
    options.scheduleMode === SCHEDULE_MODE_SUBSCRIBER_TIMEZONE;
  return {
    ...options,
    isScheduled: '1',
    scheduledAt: defaults.scheduledAt,
    scheduledLocalDate: isTimeZoneMode ? defaults.scheduledLocalDate : '',
    scheduledLocalTime: isTimeZoneMode ? defaults.scheduledLocalTime : '',
  };
}

export function isScheduleInPast(
  wasHeld: boolean,
  options: SchedulingOptions,
  now: Date,
): boolean {
  if (
    !wasHeld ||
    options.isScheduled !== '1' ||
    !options.scheduledAt ||
    options.scheduleMode === SCHEDULE_MODE_SUBSCRIBER_TIMEZONE
  ) {
    return false;
  }
  const scheduled = new Date(`${options.scheduledAt.replace(' ', 'T')}Z`);
  return scheduled.getTime() < now.getTime();
}
