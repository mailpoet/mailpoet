import React from 'react';
import MailPoet from 'mailpoet';
import { NewsletterType } from './newsletter_type';
import Heading from 'common/typography/heading/heading';
import Grid from 'common/grid';

type Props = {
  newsletter: NewsletterType
}

export const NewsletterStatsInfo = ({
  newsletter,
}: Props) => {
  const newsletterDate = newsletter.queue.scheduled_at || newsletter.queue.created_at;
  const dateFormat = Intl.DateTimeFormat(navigator.language);
  const date = dateFormat.format(new Date(newsletterDate));
  // https://github.com/microsoft/TypeScript/issues/35865#issuecomment-706723685
  // eslint-disable-next-line @typescript-eslint/ban-ts-ignore
  // @ts-ignore
  const timeFormat = new Intl.DateTimeFormat(navigator.language, { timeStyle: 'short' });
  const time = timeFormat.format(new Date(newsletterDate));

  return (
    <Grid.ThreeColumns>
      <div>
        <Heading level={1}>{newsletter.subject}</Heading>
        <p>
          {date}
          {' • '}
          {time}
        </p>
        {Array.isArray(newsletter.segments) && newsletter.segments.length && (
          <p>
            {MailPoet.I18n.t('statsToSegments')}
            {': '}
            {newsletter.segments.map((segment) => (
              <span>{segment.name}</span>
            ))}
          </p>
        )}
      </div>
      <div />
      <Grid.TwoColumns>
        <div>
          {newsletter.sender_address && (
            <p>
              {MailPoet.I18n.t('statsFromAddress')}
              {': '}
              {newsletter.sender_address}
            </p>
          )}
          {newsletter.reply_to_address && (
            <p>
              {MailPoet.I18n.t('statsReplyToAddress')}
              {': '}
              {newsletter.reply_to_address}
            </p>
          )}
          {newsletter.ga_campaign && (
            <p>
              {MailPoet.I18n.t('googleAnalytics')}
              {': '}
              {newsletter.ga_campaign}
            </p>
          )}
        </div>
        <div>
          <a
            href={newsletter.preview_url}
            target="_blank"
            rel="noopener noreferrer"
          >
            {MailPoet.I18n.t('statsPreviewNewsletter')}
          </a>
        </div>
      </Grid.TwoColumns>
    </Grid.ThreeColumns>
  );
};
