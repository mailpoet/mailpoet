import React from 'react';
import { addHours, subHours } from 'date-fns';
import MailPoet from 'mailpoet';
import NewsletterStatus from '../newsletter_status';
import Heading from '../../typography/heading/heading';

MailPoet.I18n.add('notSentYet', 'Not sent yet!');

export default {
  title: 'Listing',
  component: NewsletterStatus,
};

export const NewsletterStatuses = () => {
  const now = new Date();
  const inOneHour = addHours(now, 1);
  const inSixHours = addHours(now, 6);
  const inTwelveHours = addHours(now, 12);
  const inOneDay = addHours(now, 24);
  const inPast = subHours(now, 24);

  return (
    <>
      <Heading level={3}>Not sent yet</Heading>
      <NewsletterStatus />

      <div className="mailpoet-gap" />

      <Heading level={3}>Scheduled in the future</Heading>
      <NewsletterStatus scheduledFor={inOneHour} />
      <NewsletterStatus scheduledFor={inSixHours} />
      <NewsletterStatus scheduledFor={inTwelveHours} />
      <NewsletterStatus scheduledFor={inOneDay} />

      <div className="mailpoet-gap" />

      <Heading level={3}>Sending in progress</Heading>
      <NewsletterStatus total={200} processed={0} />
      <NewsletterStatus total={400} processed={150} />
      <NewsletterStatus scheduledFor={inPast} total={300} processed={270} />

      <div className="mailpoet-gap" />

      <Heading level={3}>Sent</Heading>
      <NewsletterStatus total={500} processed={500} />
      <NewsletterStatus total={1000} processed={1200} />
    </>
  );
};
