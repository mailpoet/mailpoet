import Hooks from 'wp-js-hooks';
import MailPoet from 'mailpoet';
import React from 'react';
import StatsBadge from 'newsletters/badges/stats.jsx';
import PropTypes from 'prop-types';

const NewsletterGeneralStats = ({ newsletter }) => {
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
  const headlineOpened = `${percentageOpenedDisplay}% ${MailPoet.I18n.t('percentageOpened')}`;
  const headlineClicked = `${percentageClickedDisplay}% ${MailPoet.I18n.t('percentageClicked')}`;
  const headlineUnsubscribed = `${percentageUnsubscribedDisplay}% ${MailPoet.I18n.t('percentageUnsubscribed')}`;
  const statsKBLink = 'https://kb.mailpoet.com/article/190-whats-a-good-email-open-rate';
  // thresholds to display badges
  const minNewslettersSent = 20;
  const minNewslettersOpened = 5;
  let statsContent;
  if (totalSent >= minNewslettersSent
    && newsletter.statistics.opened >= minNewslettersOpened
  ) {
    // display stats with badges
    statsContent = (
      <div className="mailpoet_stat_grey">
        <div className="mailpoet_stat_big mailpoet_stat_spaced">
          <StatsBadge
            stat="opened"
            rate={percentageOpened}
            headline={headlineOpened}
          />
        </div>
        <div className="mailpoet_stat_big mailpoet_stat_spaced">
          <StatsBadge
            stat="clicked"
            rate={percentageClicked}
            headline={headlineClicked}
          />
        </div>
        {Hooks.applyFilters('mailpoet_newsletters_revenues_stats', null, newsletter.statistics.revenue)}
        <div>
          <StatsBadge
            stat="unsubscribed"
            rate={percentageUnsubscribed}
            headline={headlineUnsubscribed}
          />
        </div>
      </div>
    );
  } else {
    // display stats without badges
    statsContent = (
      <div className="mailpoet_stat_grey">
        <div className="mailpoet_stat_big mailpoet_stat_spaced">
          {headlineOpened}
        </div>
        <div className="mailpoet_stat_big mailpoet_stat_spaced">
          {headlineClicked}
        </div>
        {Hooks.applyFilters('mailpoet_newsletters_revenues_stats', null, newsletter.statistics.revenue)}
        <div>
          {headlineUnsubscribed}
        </div>
      </div>
    );
  }

  return (
    <div>
      <p className="mailpoet_stat_grey mailpoet_stat_big">
        {MailPoet.I18n.t('statsTotalSent')}
        {' '}
        {parseInt(totalSent, 10).toLocaleString()}
      </p>
      {statsContent}
      { newsletter.ga_campaign && (
        <p>
          {MailPoet.I18n.t('googleAnalytics')}
          {': '}
          { newsletter.ga_campaign }
        </p>
      ) }
      <p>
        <a
          href={statsKBLink}
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

NewsletterGeneralStats.propTypes = {
  newsletter: PropTypes.shape({
    ga_campaign: PropTypes.string,
    total_sent: PropTypes.number,
    statistics: PropTypes.shape({
      clicked: PropTypes.number,
      opened: PropTypes.number,
      unsubscribed: PropTypes.number,
      revenue: PropTypes.shape({
        currency: PropTypes.string.isRequired,
        value: PropTypes.number.isRequired,
        formatted: PropTypes.string.isRequired,
        count: PropTypes.number.isRequired,
      }),
    }).isRequired,
  }).isRequired,
};

export default NewsletterGeneralStats;
