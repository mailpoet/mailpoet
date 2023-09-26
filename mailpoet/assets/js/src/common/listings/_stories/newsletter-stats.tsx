import { NewsletterStats } from '../newsletter-stats';
import { Heading } from '../../typography/heading/heading';

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
