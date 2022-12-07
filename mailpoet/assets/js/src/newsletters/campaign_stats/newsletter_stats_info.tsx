import { MailPoet } from 'mailpoet';
import { Heading } from 'common/typography/heading/heading';
import { Grid } from 'common/grid';
import { Button, SegmentTags } from 'common';
import { NewsletterType } from './newsletter_type';

type Props = {
  newsletter: NewsletterType;
};

function NewsletterStatsInfo({ newsletter }: Props) {
  const newsletterDate =
    newsletter.queue.scheduled_at || newsletter.queue.created_at;
  return (
    <Grid.ThreeColumns className="mailpoet-stats-info">
      <div className="mailpoet-grid-span-two-columns">
        <Heading level={1}>{newsletter.subject}</Heading>
        <div>
          <b>
            {MailPoet.Date.short(newsletterDate)}
            {' • '}
            {MailPoet.Date.time(newsletterDate)}
          </b>
        </div>
        {Array.isArray(newsletter.segments) && newsletter.segments.length && (
          <div>
            <span className="mailpoet-stats-info-key">
              {MailPoet.I18n.t('statsToSegments')}
            </span>
            {': '}
            <SegmentTags dimension="large" segments={newsletter.segments} />
          </div>
        )}
      </div>
      <div className="mailpoet-stats-info-sender-preview">
        <div>
          <div className="mailpoet-stats-info-key-value">
            <span className="mailpoet-stats-info-key">
              {MailPoet.I18n.t('statsFromAddress')}
              {': '}
            </span>
            {newsletter.sender_address ? newsletter.sender_address : '-'}
          </div>
          <div className="mailpoet-stats-info-key-value">
            <span className="mailpoet-stats-info-key">
              {MailPoet.I18n.t('statsReplyToAddress')}
              {': '}
            </span>
            {newsletter.reply_to_address ? newsletter.reply_to_address : '-'}
          </div>
          <div className="mailpoet-stats-info-key-value">
            <span className="mailpoet-stats-info-key">
              {MailPoet.I18n.t('googleAnalytics')}
              {': '}
            </span>
            {newsletter.ga_campaign ? newsletter.ga_campaign : '-'}
          </div>
        </div>
        <div>
          <Button
            href={newsletter.preview_url}
            target="_blank"
            rel="noopener noreferrer"
          >
            {MailPoet.I18n.t('statsPreviewNewsletter')}
          </Button>
        </div>
      </div>
    </Grid.ThreeColumns>
  );
}

NewsletterStatsInfo.displayName = 'NewsletterStatsInfo';
export { NewsletterStatsInfo };
