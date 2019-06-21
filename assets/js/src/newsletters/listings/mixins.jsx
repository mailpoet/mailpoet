import React from 'react';
import ReactDOM from 'react-dom';
import ReactStringReplace from 'react-string-replace';
import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';
import moment from 'moment';
import jQuery from 'jquery';
import Hooks from 'wp-js-hooks';
import StatsBadge from 'newsletters/badges/stats.jsx';
import HelpTooltip from 'help-tooltip.jsx';

function trackStatsCTAClicked() {
  MailPoet.trackEvent(
    'User has clicked a CTA to view detailed stats',
    { 'MailPoet Free version': window.mailpoet_version }
  );
}

function wrapInLink(content, params, id, totalSent) {
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
    <Link
      key={`stats-${id}`}
      to={params.link}
      onClick={params.onClick || null}
    >
      {content}
    </Link>
  );
}

const addStatsCTALink = (params) => {
  if (window.mailpoet_premium_active) {
    return params;
  }
  const newParams = params;
  newParams.link = 'admin.php?page=mailpoet-premium';
  newParams.externalLink = true;
  newParams.onClick = trackStatsCTAClicked;
  return newParams;
};

export const renderStatistics = (newsletter, isSent, currentTime) => {
  let sent = isSent;
  if (sent === undefined) {
    // condition for standard and post notification listings
    sent = newsletter.statistics
        && newsletter.queue
        && newsletter.queue.status !== 'scheduled';
  }
  if (!sent) {
    return (
      <span>{MailPoet.I18n.t('notSentYet')}</span>
    );
  }

  let params = {};
  Hooks.addFilter('mailpoet_newsletters_listing_stats_before', 'mailpoet', addStatsCTALink);
  params = Hooks.applyFilters('mailpoet_newsletters_listing_stats_before', params, newsletter);

  // welcome emails provide explicit total_sent value
  const totalSent = Number((newsletter.total_sent || newsletter.queue.count_processed));

  let percentageClicked = 0;
  let percentageOpened = 0;
  let percentageUnsubscribed = 0;
  let revenue = null;

  if (totalSent > 0) {
    percentageClicked = (newsletter.statistics.clicked * 100) / totalSent;
    percentageOpened = (newsletter.statistics.opened * 100) / totalSent;
    percentageUnsubscribed = (newsletter.statistics.unsubscribed * 100) / totalSent;
    revenue = newsletter.statistics.revenue;
  }

  // format to 1 decimal place
  const percentageClickedDisplay = MailPoet.Num.toLocaleFixed(percentageClicked, 1);
  const percentageOpenedDisplay = MailPoet.Num.toLocaleFixed(percentageOpened, 1);
  const percentageUnsubscribedDisplay = MailPoet.Num.toLocaleFixed(percentageUnsubscribed, 1);

  let showStatsTimeout;
  let newsletterDate;
  let sentHoursAgo;
  let tooEarlyForStats;
  let showKbLink;
  if (currentTime !== undefined) {
    // standard emails and post notifications:
    // display green box for newsletters that were just sent
    showStatsTimeout = 6; // in hours
    newsletterDate = newsletter.queue.scheduled_at || newsletter.queue.created_at;
    sentHoursAgo = moment(currentTime).diff(moment(newsletterDate), 'hours');
    tooEarlyForStats = sentHoursAgo < showStatsTimeout;
    showKbLink = true;
  } else {
    // welcome emails: no green box and KB link
    tooEarlyForStats = false;
    showKbLink = false;
  }

  const improveStatsKBLink = 'http://beta.docs.mailpoet.com/article/191-how-to-improve-my-open-and-click-rates';

  // thresholds to display badges
  const minNewslettersSent = 20;
  const minNewsletterOpens = 5;

  let openedAndClickedStats;
  if (totalSent >= minNewslettersSent
      && newsletter.statistics.opened >= minNewsletterOpens
      && !tooEarlyForStats
  ) {
    // display stats with badges
    openedAndClickedStats = (
      <div className="mailpoet_stats_text">
        <div>
          <span>
            { percentageOpenedDisplay }
              %
            {' '}
          </span>
          <StatsBadge
            stat="opened"
            rate={percentageOpened}
            tooltipId={`opened-${newsletter.id}`}
          />
        </div>
        <div>
          <span>
            { percentageClickedDisplay }
              %
            {' '}
          </span>
          <StatsBadge
            stat="clicked"
            rate={percentageClicked}
            tooltipId={`clicked-${newsletter.id}`}
          />
        </div>
        <div>
          <span className="mailpoet_stat_hidden">
            { percentageUnsubscribedDisplay }
              %
          </span>
        </div>
      </div>
    );
  } else {
    // display simple stats
    openedAndClickedStats = (
      <div>
        <span className="mailpoet_stats_text">
          { percentageOpenedDisplay }
            %,
          { ' ' }
          { percentageClickedDisplay }
            %
          <span className="mailpoet_stat_hidden">
              ,
            {' '}
            { percentageUnsubscribedDisplay }
              %
          </span>
        </span>
      </div>
    );
  }

  const wrapContentInLink = (content, idPrefix) => wrapInLink(
    content,
    params,
    `${idPrefix}-${newsletter.id}`,
    totalSent
  );

  const content = (
    <>
      { wrapContentInLink(openedAndClickedStats, 'opened-and-clicked') }
      { revenue !== null && revenue.value > 0 && (
      <div className="mailpoet_stats_text">
        { wrapContentInLink(revenue.formatted, 'revenue') }
        {' '}
        <HelpTooltip
          tooltip={MailPoet.I18n.t('revenueStatsTooltip')}
          place="left"
          tooltipId="helpTooltipStatsRevenue"
        />
      </div>
      ) }
      { tooEarlyForStats && wrapContentInLink(
        (
          <div className="mailpoet_badge mailpoet_badge_green">
            {MailPoet.I18n.t('checkBackInHours').replace('%$1d', showStatsTimeout - sentHoursAgo)}
          </div>
        ),
        'check-back'
      ) }
    </>
  );

  // thresholds to display bad open rate help
  const maxPercentageOpened = 5;
  const minSentHoursAgo = 24;
  const minTotalSent = 10;

  let afterContent;
  if (showKbLink
      && percentageOpened < maxPercentageOpened
      && sentHoursAgo >= minSentHoursAgo
      && totalSent >= minTotalSent
  ) {
    // help link for bad open rate
    afterContent = (
      <div>
        <a
          href={improveStatsKBLink}
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
    <div>
      {content}
      {afterContent}
    </div>
  );
};

export const addStatsCTAAction = (actions) => {
  if (window.mailpoet_premium_active) {
    return actions;
  }
  actions.unshift({
    name: 'stats',
    link: function link() {
      return (
        <a href="admin.php?page=mailpoet-premium" onClick={trackStatsCTAClicked}>
          {MailPoet.I18n.t('statsListingActionTitle')}
        </a>
      );
    },
    display: function display(newsletter) {
      // welcome emails provide explicit total_sent value
      const countProcessed = newsletter.queue && newsletter.queue.count_processed;
      return Number(newsletter.total_sent || countProcessed) > 0;
    },
  });
  return actions;
};

export const checkMailerStatus = (state) => {
  if (state.meta.mta_log.error && state.meta.mta_log.error.operation === 'authorization') {
    MailPoet.Notice.hide('mailpoet_notice_being_sent');
    MailPoet.Notice.error(
      state.meta.mta_log.error.error_message,
      { static: true, id: 'mailpoet_authorization_error' }
    );
    jQuery('.js-button-resume-sending').on('click', () => {
      jQuery('[data-id="mailpoet_authorization_error"]').slideUp();
    });
  }
};

export const checkCronStatus = (state) => {
  if (state.meta.cron_accessible !== false) {
    MailPoet.Notice.hide('mailpoet_cron_error');
    return;
  }

  const cronPingCheckNotice = ReactStringReplace(
    MailPoet.I18n.t('cronNotAccessibleNotice'),
    /\[link\](.*?)\[\/link\]/g,
    match => (
      <a
        href="https://beta.docs.mailpoet.com/article/231-sending-does-not-work"
        target="_blank"
        rel="noopener noreferrer"
        key="check-cron"
      >
        { match }
      </a>
    )
  );

  MailPoet.Notice.error(
    '',
    { static: true, id: 'mailpoet_cron_error' }
  );

  ReactDOM.render(
    (
      <div>
        <p>{cronPingCheckNotice}</p>
      </div>
    ),
    jQuery('[data-id="mailpoet_cron_error"]')[0]
  );
};
