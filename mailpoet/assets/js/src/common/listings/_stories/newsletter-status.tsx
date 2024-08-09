import { addHours, subHours } from 'date-fns';
import { NewsletterStatus as NewsletterStatusEnum } from 'common/newsletter';
import { NewsletterStatus } from '../newsletter-status';
import { Heading } from '../../typography';

export default {
  title: 'Listing',
  component: NewsletterStatus,
};

export function NewsletterStatuses() {
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
      <NewsletterStatus scheduledFor={inOneHour.toISOString()} />
      <NewsletterStatus scheduledFor={inSixHours.toISOString()} />
      <NewsletterStatus scheduledFor={inTwelveHours.toISOString()} />
      <NewsletterStatus scheduledFor={inOneDay.toISOString()} />
      <NewsletterStatus scheduledFor={inOneDay.toISOString()} isPaused />

      <div className="mailpoet-gap" />

      <Heading level={3}>Sending in progress</Heading>
      <NewsletterStatus total={200} processed={0} />
      <NewsletterStatus total={400} processed={150} />
      <NewsletterStatus
        scheduledFor={inPast.toISOString()}
        total={300}
        processed={270}
      />
      <NewsletterStatus
        scheduledFor={inPast.toISOString()}
        total={300}
        processed={270}
        isPaused
      />

      <div className="mailpoet-gap" />

      <Heading level={3}>Sent</Heading>
      <NewsletterStatus total={500} processed={500} />
      <NewsletterStatus total={1000} processed={1200} />
      <NewsletterStatus total={1000} processed={1200} isPaused />

      <div className="mailpoet-gap" />

      <Heading level={3}>Sent without queue</Heading>
      <NewsletterStatus status={NewsletterStatusEnum.Sent} />
    </>
  );
}
