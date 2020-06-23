import React from 'react';
import { addHours, subHours } from 'date-fns';
import MailPoet from 'mailpoet';
import NewsletterStatus from '../newsletter_status';

MailPoet.I18n.add('notSentYet', 'Not sent yet!');

export default {
  title: 'NewsletterStatus',
};

export const NotSentYet = () => (<NewsletterStatus />);

export const ScheduledInTheFuture = () => {
  const now = new Date();
  const inOneHour = addHours(now, 1);
  const inSixHours = addHours(now, 6);
  const inTwelveHours = addHours(now, 12);
  const inOneDay = addHours(now, 24);
  return (
    <>
      <div>
        <h2>Scheduled in 1 hour</h2>
        <NewsletterStatus scheduledFor={inOneHour} />
      </div>
      <div>
        <h2>Scheduled in 6 hours</h2>
        <NewsletterStatus scheduledFor={inSixHours} />
      </div>
      <div>
        <h2>Scheduled in 12 hours</h2>
        <NewsletterStatus scheduledFor={inTwelveHours} />
      </div>
      <div>
        <h2>Scheduled in 1 day</h2>
        <NewsletterStatus scheduledFor={inOneDay} />
      </div>
    </>
  );
};

export const SendingInProgress = () => {
  const scheduledFor = subHours(new Date(), 1);
  return (
    <>
      <div>
        <h2>Progress at 0%</h2>
        <NewsletterStatus scheduledFor={scheduledFor} total={100} processed={0} />
      </div>
      <div>
        <h2>Progress at 30%</h2>
        <NewsletterStatus scheduledFor={scheduledFor} total={100} processed={30} />
      </div>
      <div>
        <h2>Progress at 90%</h2>
        <NewsletterStatus scheduledFor={scheduledFor} total={100} processed={90} />
      </div>
    </>
  );
};


export const Sent = () => (
  <NewsletterStatus
    scheduledFor={subHours(new Date(), 1)}
    total={100}
    processed={100}
  />
);
