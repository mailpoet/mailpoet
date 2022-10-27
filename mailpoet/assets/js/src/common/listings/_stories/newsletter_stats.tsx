import { MailPoet } from 'mailpoet';
import { NewsletterStats } from '../newsletter_stats';
import { Heading } from '../../typography/heading/heading';

MailPoet.I18n.add('excellentBadgeName', 'Excellent');
MailPoet.I18n.add('excellentBadgeTooltip', 'Congrats!');
MailPoet.I18n.add('goodBadgeName', 'Good');
MailPoet.I18n.add('goodBadgeTooltip', 'Good stuff.');
MailPoet.I18n.add('criticalBadgeName', 'Critical');
MailPoet.I18n.add('criticalBadgeTooltip', 'Something to improve.');
MailPoet.I18n.add('openedStatTooltipExcellent', 'above 30%');
MailPoet.I18n.add('openedStatTooltipGood', 'between 10 and 30%');
MailPoet.I18n.add('openedStatTooltipCritical', 'under 10%');
MailPoet.I18n.add('clickedStatTooltipExcellent', 'above 3%');
MailPoet.I18n.add('clickedStatTooltipGood', 'between 1 and 3%');
MailPoet.I18n.add('clickedStatTooltipCritical', 'under 1%');
MailPoet.I18n.add(
  'revenueStatsTooltipShort',
  'Revenues by customer who clicked on this email in the last 2 weeks.',
);

export default {
  title: 'Listing',
  component: NewsletterStats,
};

export function NewsletterStatsComponent() {
  return (
    <>
      <Heading level={3}>With badges</Heading>

      <NewsletterStats opened={1} clicked={0.1} newsletterId={1} />
      <div className="mailpoet-gap" />
      <NewsletterStats opened={11} clicked={1.1} newsletterId={2} />
      <div className="mailpoet-gap" />
      <NewsletterStats opened={31} clicked={3.1} newsletterId={3} />

      <div className="mailpoet-gap" />

      <Heading level={3}>With badges and revenues</Heading>

      <NewsletterStats
        opened={1}
        clicked={0.1}
        revenues="10€"
        newsletterId={4}
      />
      <div className="mailpoet-gap" />
      <NewsletterStats
        opened={11}
        clicked={1.1}
        revenues="100€"
        newsletterId={5}
      />
      <div className="mailpoet-gap" />
      <NewsletterStats
        opened={31}
        clicked={3.1}
        revenues="1000€"
        newsletterId={6}
      />

      <div className="mailpoet-gap" />

      <Heading level={3}>No badges</Heading>

      <NewsletterStats hideBadges opened={1} clicked={0.1} newsletterId={7} />
      <div className="mailpoet-gap" />
      <NewsletterStats hideBadges opened={11} clicked={1.1} newsletterId={8} />
      <div className="mailpoet-gap" />
      <NewsletterStats hideBadges opened={31} clicked={3.1} newsletterId={9} />

      <div className="mailpoet-gap" />

      <Heading level={3}>No badges, with revenues</Heading>

      <NewsletterStats
        hideBadges
        opened={1}
        clicked={0.1}
        revenues="10€"
        newsletterId={10}
      />
      <div className="mailpoet-gap" />
      <NewsletterStats
        hideBadges
        opened={11}
        clicked={1.1}
        revenues="100€"
        newsletterId={11}
      />
      <div className="mailpoet-gap" />
      <NewsletterStats
        hideBadges
        opened={31}
        clicked={3.1}
        revenues="1000€"
        newsletterId={12}
      />
    </>
  );
}
