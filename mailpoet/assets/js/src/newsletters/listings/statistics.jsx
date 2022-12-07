import moment from 'moment';
import { MailPoet } from 'mailpoet';
import { Hooks } from 'wp-js-hooks';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import { Tag, withBoundary } from 'common';
import { trackStatsCTAClicked } from 'newsletters/listings/utils.jsx';
import { NewsletterStats } from 'common/listings/newsletter_stats';

const wrapInLink = (content, params, id, totalSent) => {
  if (totalSent <= 0 || !params.link) {
    return content;
  }

  if (params.externalLink) {
    return (
      <a
        key={`stats-${id}`}
        href={params.link}
        onClick={params.onClick || null}
      >
        {content}
      </a>
    );
  }
  return (
    <Link key={`stats-${id}`} to={params.link} onClick={params.onClick || null}>
      {content}
    </Link>
  );
};

function Statistics({ newsletter, isSent, currentTime }) {
  let sent = isSent;
  if (sent === undefined) {
    // condition for standard and post notification listings
    sent =
      newsletter.statistics &&
      newsletter.queue &&
      newsletter.queue.status !== 'scheduled';
  }
  if (!sent) {
    return null;
  }

  const params = {
    link: `/stats/${newsletter.id}`,
    onClick: Hooks.applyFilters(
      'mailpoet_newsletters_listing_stats_tracking',
      trackStatsCTAClicked,
    ),
  };

  // welcome emails provide explicit total_sent value
  const totalSent = Number(
    newsletter.total_sent || newsletter.queue.count_processed,
  );

  let percentageClicked = 0;
  let percentageOpened = 0;
  let revenue = null;

  if (totalSent > 0) {
    percentageClicked = (newsletter.statistics.clicked * 100) / totalSent;
    percentageOpened = (newsletter.statistics.opened * 100) / totalSent;
    revenue = newsletter.statistics.revenue;
  }

  let showStatsTimeout;
  let newsletterDate;
  let sentHoursAgo;
  let tooEarlyForStats;
  let showKbLink;
  if (currentTime !== undefined) {
    // standard emails and post notifications:
    // display green box for newsletters that were just sent
    showStatsTimeout = 6; // in hours
    newsletterDate =
      newsletter.queue.scheduled_at || newsletter.queue.created_at;
    sentHoursAgo = moment(currentTime).diff(moment(newsletterDate), 'hours');
    tooEarlyForStats = sentHoursAgo < showStatsTimeout;
    showKbLink = true;
  } else {
    // welcome emails: no green box and KB link
    tooEarlyForStats = false;
    showKbLink = false;
  }

  const improveStatsKBLink =
    'https://kb.mailpoet.com/article/191-how-to-improve-my-open-and-click-rates';

  // thresholds to display badges
  const minNewslettersSent = 20;
  const minNewsletterOpens = 5;

  const showBadges =
    totalSent >= minNewslettersSent &&
    newsletter.statistics.opened >= minNewsletterOpens &&
    !tooEarlyForStats;

  const wrapContentInLink = (content, idPrefix) =>
    wrapInLink(content, params, `${idPrefix}-${newsletter.id}`, totalSent);

  const openedClickedAndRevenueStats = (
    <NewsletterStats
      opened={percentageOpened}
      clicked={percentageClicked}
      revenues={revenue && revenue.value > 0 ? revenue.formatted : null}
      hideBadges={!showBadges}
      newsletterId={newsletter.id}
      wrapContentInLink={wrapContentInLink}
    />
  );

  const content = (
    <>
      {openedClickedAndRevenueStats}
      {tooEarlyForStats &&
        wrapContentInLink(
          <Tag
            className="mailpoet-listing-stats-too-early"
            dimension="large"
            variant="excellent"
            isInverted
          >
            {MailPoet.I18n.t('checkBackInHours').replace(
              '%1$d',
              showStatsTimeout - sentHoursAgo,
            )}
          </Tag>,
          'check-back',
        )}
    </>
  );

  // thresholds to display bad open rate help
  const maxPercentageOpened = 5;
  const minSentHoursAgo = 24;
  const minTotalSent = 10;

  let afterContent;
  if (
    showKbLink &&
    percentageOpened < maxPercentageOpened &&
    sentHoursAgo >= minSentHoursAgo &&
    totalSent >= minTotalSent
  ) {
    // help link for bad open rate
    afterContent = (
      <div>
        <a
          href={improveStatsKBLink}
          data-beacon-article="58f671152c7d3a057f8858e8"
          target="_blank"
          rel="noopener noreferrer"
          className="mailpoet_stat_link_small"
        >
          {MailPoet.I18n.t('improveThisLinkText')}
        </a>
      </div>
    );
  }

  return (
    <>
      {content}
      {afterContent}
    </>
  );
}

const StatisticsPropType = PropTypes.shape({
  clicked: PropTypes.number,
  opened: PropTypes.number,
  unsubscribed: PropTypes.number,
  revenue: PropTypes.shape({
    count: PropTypes.number,
    currency: PropTypes.string,
    formatted: PropTypes.string,
    value: PropTypes.number,
  }),
});

const QueuePropType = PropTypes.shape({
  status: PropTypes.string,
  count_processed: PropTypes.string.isRequired,
  count_total: PropTypes.string.isRequired,
  created_at: PropTypes.string,
  scheduled_at: PropTypes.string,
});

Statistics.propTypes = {
  newsletter: PropTypes.shape({
    id: PropTypes.number.isRequired,
    queue: PropTypes.oneOfType([QueuePropType, PropTypes.bool]),
    total_sent: PropTypes.number,
    statistics: PropTypes.oneOfType([StatisticsPropType, PropTypes.bool]),
  }).isRequired,
  isSent: PropTypes.bool,
  currentTime: PropTypes.string,
};

Statistics.defaultProps = {
  isSent: undefined,
  currentTime: undefined,
};
Statistics.displayName = 'NewsletterStatistics';
const StatisticsWithBoundary = withBoundary(Statistics);
export { StatisticsWithBoundary as Statistics };
