import React from 'react';
import MailPoet from 'mailpoet';
import classNames from 'classnames';
import {
  isFuture, isPast, differenceInMinutes,
} from 'date-fns';
import t from 'common/functions/t';

type NewsletterStatusProps = {
  scheduledFor?: Date,
  processed?: number,
  total?: number,
  isPaused?: boolean,
  status?: string,
}

const NewsletterStatus = ({
  scheduledFor,
  processed,
  total,
  isPaused,
  status,
}: NewsletterStatusProps) => {
  const unknown = !scheduledFor && !processed && !total;
  const scheduled = scheduledFor && isFuture(scheduledFor);
  const inProgress = (!scheduledFor || isPast(scheduledFor)) && processed < total;
  const sent = (!scheduledFor || isPast(scheduledFor)) && processed >= total;
  const sentWithoutQueue = status === 'sent' && total === undefined;
  let percentage = 0;
  let label = (<>{t('notSentYet')}</>);
  if (scheduled) {
    const minutesIn12Hours = 720;
    const minutesLeft = differenceInMinutes(scheduledFor, new Date());
    label = (
      <>
        {MailPoet.Date.short(scheduledFor)}
        <br />
        {MailPoet.Date.time(scheduledFor)}
      </>
    );
    if (minutesLeft < minutesIn12Hours) {
      percentage = 100 * (minutesLeft / minutesIn12Hours);
    } else {
      percentage = 100;
    }
  } else if (inProgress) {
    label = (<>{`${processed} / ${total}`}</>);
    percentage = 100 * (processed / total);
  } else if (sent) {
    label = (<>{`${total} / ${total}`}</>);
    percentage = 100;
  } else if (sentWithoutQueue) {
    label = (<>{t('sent')}</>);
    percentage = 100;
  }
  if (isPaused && !sent && !sentWithoutQueue) {
    label = (<>{t('paused')}</>);
  }
  return (
    <div className={classNames({
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
};

type CircularProgressProps = {
  percentage: number
}

const CircularProgress = ({ percentage }: CircularProgressProps) => {
  const perimeter = 16 * Math.PI;
  const filled = perimeter * (percentage / 100);
  const empty = perimeter - filled;
  return (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="8" className="mailpoet-listing-status-percentage-background" />
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
};

export const ScheduledIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
    <path className="mailpoet-listing-status-scheduled-icon" strokeLinecap="round" d="M12 7L12 12 15 15" />
  </svg>
);

export default NewsletterStatus;
