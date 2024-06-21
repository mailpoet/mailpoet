import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import classnames from 'classnames';
import { addDays, differenceInMinutes, isFuture, isPast } from 'date-fns';
import {
  NewsLetter,
  NewsletterStatus as NewsletterStatusEnum,
} from 'common/newsletter';
import { Tooltip } from '../tooltip/tooltip';

type CircularProgressProps = {
  percentage: number;
};

function CircularProgress({ percentage }: CircularProgressProps) {
  const perimeter = 16 * Math.PI;
  const filled = perimeter * (percentage / 100);
  const empty = perimeter - filled;
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
    >
      <circle
        cx="12"
        cy="12"
        r="8"
        className="mailpoet-listing-status-percentage-background"
      />
      <circle
        r="8"
        cx="12"
        cy="12"
        fill="none"
        strokeDashoffset={perimeter / 4}
        strokeDasharray={`${filled} ${empty}`}
        className="mailpoet-listing-status-percentage"
      />
    </svg>
  );
}

export function ScheduledIcon() {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
    >
      <path
        className="mailpoet-listing-status-scheduled-icon"
        strokeLinecap="round"
        d="M12 7L12 12 15 15"
      />
    </svg>
  );
}

type NewsletterStatusProps = {
  scheduledFor?: Date;
  processed?: number;
  total?: number;
  isPaused?: boolean;
  status?: NewsletterStatusEnum;
  logs?: NewsLetter['logs'];
};

function NewsletterStatus({
  scheduledFor,
  processed,
  total,
  isPaused,
  status,
  logs = [],
}: NewsletterStatusProps) {
  const isCorrupt = status === 'corrupt';
  const unknown = !scheduledFor && !processed && !total;
  const scheduled = scheduledFor && isFuture(scheduledFor);
  const inProgress =
    (!scheduledFor || isPast(scheduledFor)) && processed < total && !isCorrupt;
  const sent = status === 'sent' && processed >= total;
  const sentWithoutQueue = status === 'sent' && total === undefined;
  let percentage = 0;
  let label: string | JSX.Element = __('Not sent yet!', 'mailpoet');

  if (sent) {
    label = `${MailPoet.Num.toLocaleFixed(
      total,
    )} / ${MailPoet.Num.toLocaleFixed(total)}`;
    percentage = 100;
  } else if (scheduled) {
    const scheduledDate = MailPoet.Date.short(scheduledFor);
    const scheduledTime = MailPoet.Date.time(scheduledFor);
    const now = new Date();
    const tomorrow = addDays(now, 1);
    const isScheduledForToday = MailPoet.Date.short(now) === scheduledDate;
    const isScheduledForTomorrow =
      MailPoet.Date.short(tomorrow) === scheduledDate;
    if (isScheduledForToday || isScheduledForTomorrow) {
      const randomId = Math.random().toString(36).substring(2, 15);
      const dateWord = isScheduledForToday
        ? __('Today', 'mailpoet')
        : __('Tomorrow', 'mailpoet');
      label = (
        <>
          <span data-tip data-tooltip-id={randomId}>
            {dateWord}
          </span>
          <Tooltip place="right" id={randomId}>
            {scheduledDate}
          </Tooltip>
          <br />
          {scheduledTime}
        </>
      );
    } else {
      label = (
        <>
          {scheduledDate}
          <br />
          {scheduledTime}
        </>
      );
    }
    const minutesIn12Hours = 720;
    const minutesLeft = differenceInMinutes(scheduledFor, now);
    if (minutesLeft < minutesIn12Hours) {
      percentage = 100 * (minutesLeft / minutesIn12Hours);
    } else {
      percentage = 100;
    }
  } else if (inProgress) {
    label = `${MailPoet.Num.toLocaleFixed(
      processed,
    )} / ${MailPoet.Num.toLocaleFixed(total)}`;
    percentage = 100 * (processed / total);
  } else if (sentWithoutQueue) {
    label = __('Sent', 'mailpoet');
    percentage = 100;
  }
  if (isPaused && !sent && !sentWithoutQueue) {
    label = __('Paused', 'mailpoet');
  }

  if (isCorrupt) {
    label = __('There was a problem with rendering!', 'mailpoet');
  }

  return (
    <div
      className={classnames({
        'mailpoet-listing-status': true,
        'mailpoet-listing-status-unknown': unknown,
        'mailpoet-listing-status-scheduled': scheduled,
        'mailpoet-listing-status-in-progress': inProgress,
        'mailpoet-listing-status-sent': sent || sentWithoutQueue,
        'mailpoet-listing-status-corrupt': isCorrupt,
      })}
    >
      {scheduled && !sent && <ScheduledIcon />}
      {!isCorrupt && <CircularProgress percentage={percentage} />}
      <div className="mailpoet-listing-status-label">{label}</div>
      {isCorrupt && logs.length > 0 && (
        <div className="mailpoet-listing-status-message">{logs.join('\n')}</div>
      )}
    </div>
  );
}

NewsletterStatus.displayName = 'NewsletterStatus';
export { NewsletterStatus };
