import { isHeldQueue } from 'newsletters/listings/queue-labels';
import { SCHEDULE_MODE_SUBSCRIBER_TIMEZONE } from 'common/newsletter-schedule-mode';

type SchedulingOptions = {
  isScheduled?: string;
  scheduledAt?: string;
  scheduleMode?: string;
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

export function withoutStaleSchedule<T extends SchedulingOptions>(
  options: T,
): Omit<T, 'scheduledAt'> {
  const { scheduledAt, ...rest } = options;
  return { ...rest, isScheduled: '0' };
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
