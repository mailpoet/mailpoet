import React from 'react';
import MailPoet from 'mailpoet';
import Hooks from 'wp-js-hooks';
import Grid from 'common/grid';
import StatsBadge from 'common/listings/newsletter_stats/stats';

import { NewsletterType } from './newsletter_type';

type Props = {
  newsletter: NewsletterType
}

const minNewslettersSent = 20;
const minNewslettersOpened = 5;

export const NewsletterGeneralStats = ({
  newsletter,
}: Props) => {
  const totalSent = newsletter.total_sent || 0;

  let percentageClicked = 0;
  let percentageOpened = 0;
  let percentageUnsubscribed = 0;
  if (totalSent > 0) {
    percentageClicked = (newsletter.statistics.clicked * 100) / totalSent;
    percentageOpened = (newsletter.statistics.opened * 100) / totalSent;
    percentageUnsubscribed = (newsletter.statistics.unsubscribed * 100) / totalSent;
  }
  // format to 1 decimal place
  const percentageClickedDisplay = MailPoet.Num.toLocaleFixed(percentageClicked, 1);
  const percentageOpenedDisplay = MailPoet.Num.toLocaleFixed(percentageOpened, 1);
  const percentageUnsubscribedDisplay = MailPoet.Num.toLocaleFixed(percentageUnsubscribed, 1);

  const headlineClicked = `${percentageClickedDisplay}% ${MailPoet.I18n.t('percentageClicked')}`;

  const displayBadges = ((totalSent >= minNewslettersSent)
    && (newsletter.statistics.opened >= minNewslettersOpened)
  );

  const opened = (
    <>
      <div>{`${percentageOpenedDisplay}% ${MailPoet.I18n.t('percentageOpened')}`}</div>
      {displayBadges && (
        <StatsBadge
          stat="opened"
          rate={percentageOpened}
          tooltipId={`opened-${newsletter.id || '0'}`}
        />
      )}
    </>
  );

  const unsubscribed = (
    <>
      <div>{`${percentageUnsubscribedDisplay}% ${MailPoet.I18n.t('percentageUnsubscribed')}`}</div>
      {displayBadges && (
        <StatsBadge
          stat="unsubscribed"
          rate={percentageUnsubscribed}
          tooltipId={`unsubscribed-${newsletter.id || '0'}`}
        />
      )}
    </>
  );

  const clicked = (
    <>
      <div>{`${percentageClickedDisplay}% ${MailPoet.I18n.t('percentageClicked')}`}</div>
      {displayBadges && (
        <StatsBadge
          stat="clicked"
          rate={percentageClicked}
          tooltipId={`clicked-${newsletter.id || '0'}`}
        />
      )}
    </>
  );

  return (
    <div className="mailpoet-stats-general">
      <Grid.ThreeColumns>
        <div>
          {MailPoet.I18n.t('statsTotalSent')}
          {': '}
          {totalSent.toLocaleString()}
          {opened}
        </div>
        <div>
          {unsubscribed}

          {clicked}
        </div>
        <div>
          {Hooks.applyFilters('mailpoet_newsletters_revenues_stats', null, newsletter.statistics.revenue)}
        </div>
      </Grid.ThreeColumns>
      <p>
        <a
          href="https://kb.mailpoet.com/article/190-whats-a-good-email-open-rate"
          target="_blank"
          rel="noopener noreferrer"
          data-beacon-article="58f671152c7d3a057f8858e8"
        >
          {MailPoet.I18n.t('readMoreOnStats')}
        </a>
      </p>
    </div>
  );
};
