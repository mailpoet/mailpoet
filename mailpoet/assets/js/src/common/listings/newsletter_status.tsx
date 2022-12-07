import { MailPoet } from 'mailpoet';
import classnames from 'classnames';
import { addDays, differenceInMinutes, isFuture, isPast } from 'date-fns';
import { t } from 'common/functions/t';
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
  status?: string;
};

function NewsletterStatus({
  scheduledFor,
  processed,
  total,
  isPaused,
  status,
}: NewsletterStatusProps) {
  const unknown = !scheduledFor && !processed && !total;
  const scheduled = scheduledFor && isFuture(scheduledFor);
  const inProgress =
    (!scheduledFor || isPast(scheduledFor)) && processed < total;
  const sent = (!scheduledFor || isPast(scheduledFor)) && processed >= total;
  const sentWithoutQueue = status === 'sent' && total === undefined;
  let percentage = 0;
  let label: string | JSX.Element = t('notSentYet');
  if (scheduled) {
    const scheduledDate = MailPoet.Date.short(scheduledFor);
    const scheduledTime = MailPoet.Date.time(scheduledFor);
    const now = new Date();
    const tomorrow = addDays(now, 1);
    const isScheduledForToday = MailPoet.Date.short(now) === scheduledDate;
    const isScheduledForTomorrow =
      MailPoet.Date.short(tomorrow) === scheduledDate;
    if (isScheduledForToday || isScheduledForTomorrow) {
      const randomId = Math.random().toString(36).substring(2, 15);
      const dateWord = isScheduledForToday ? t('today') : t('tomorrow');
      label = (
        <>
          <span data-tip data-for={randomId}>
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
  } else if (sent) {
    label = `${MailPoet.Num.toLocaleFixed(
      total,
    )} / ${MailPoet.Num.toLocaleFixed(total)}`;
    percentage = 100;
  } else if (sentWithoutQueue) {
    label = t('sent');
    percentage = 100;
  }
  if (isPaused && !sent && !sentWithoutQueue) {
    label = t('paused');
  }
  return (
    <div
      className={classnames({
        'mailpoet-listing-status': true,
        'mailpoet-listing-status-unknown': unknown,
        'mailpoet-listing-status-scheduled': scheduled,
        'mailpoet-listing-status-in-progress': inProgress,
        'mailpoet-listing-status-sent': sent || sentWithoutQueue,
      })}
    >
      {scheduled && <ScheduledIcon />}
      <CircularProgress percentage={percentage} />
      <div className="mailpoet-listing-status-label">{label}</div>
    </div>
  );
}

NewsletterStatus.displayName = 'NewsletterStatus';
export { NewsletterStatus };
