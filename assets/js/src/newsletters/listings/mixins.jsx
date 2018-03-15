import React from 'react';
import ReactDOM from 'react-dom';
import ReactStringReplace from 'react-string-replace';
import { Link } from 'react-router';
import MailPoet from 'mailpoet';
import classNames from 'classnames';
import moment from 'moment';
import jQuery from 'jquery';
import Hooks from 'wp-js-hooks';
import StatsBadge from 'newsletters/badges/stats.jsx';

const QueueMixin = {
  pauseSending: function (newsletter) {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'sendingQueue',
      action: 'pause',
      data: {
        newsletter_id: newsletter.id,
      },
    }).done(() => {
      jQuery(`#resume_${newsletter.id}`).show();
      jQuery(`#pause_${newsletter.id}`).hide();
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    });
  },
  resumeSending: function (newsletter) {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'sendingQueue',
      action: 'resume',
      data: {
        newsletter_id: newsletter.id,
      },
    }).done(() => {
      jQuery(`#pause_${newsletter.id}`).show();
      jQuery(`#resume_${newsletter.id}`).hide();
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    });
  },
  renderQueueStatus: function (newsletter, mailerLog) {
    if (!newsletter.queue) {
      return (
        <span>{MailPoet.I18n.t('notSentYet')}</span>
      );
    } else if (mailerLog.status === 'paused' && newsletter.queue.status !== 'completed') {
      return (
        <span>{MailPoet.I18n.t('paused')}</span>
      );
    }
    if (newsletter.queue.status === 'scheduled') {
      return (
        <span>
          { MailPoet.I18n.t('scheduledFor') } { MailPoet.Date.format(newsletter.queue.scheduled_at) }
        </span>
      );
    }
    const progressClasses = classNames(
        'mailpoet_progress',
        { mailpoet_progress_complete: newsletter.queue.status === 'completed' }
      );

      // calculate percentage done
    let percentage = Math.round(
        (newsletter.queue.count_processed * 100) / (newsletter.queue.count_total)
      );

    let label;

    if (newsletter.queue.status === 'completed') {
      label = (
        <span>
          {
              MailPoet.I18n.t('newsletterQueueCompleted')
              .replace('%$1d', parseInt(newsletter.queue.count_processed, 10).toLocaleString())
              .replace('%$2d', parseInt(newsletter.queue.count_total, 10).toLocaleString())
            }
        </span>
        );
    } else {
      label = (
        <span>
          { newsletter.queue.count_processed } / { newsletter.queue.count_total }
            &nbsp;&nbsp;
          <a
            id={`resume_${newsletter.id}`}
            className="button"
            style={{ display: (newsletter.queue.status === 'paused')
                ? 'inline-block' : 'none' }}
            href="javascript:;"
            onClick={this.resumeSending.bind(null, newsletter)}
          >{MailPoet.I18n.t('resume')}</a>
          <a
            id={`pause_${newsletter.id}`}
            className="button mailpoet_pause"
            style={{ display: (newsletter.queue.status === null)
                  ? 'inline-block' : 'none' }}
            href="javascript:;"
            onClick={this.pauseSending.bind(null, newsletter)}
          >{MailPoet.I18n.t('pause')}</a>
        </span>
        );
    }

    let progressBarWidth = 0;

    if (isNaN(percentage)) {
      percentage = MailPoet.I18n.t('noSubscribers');
    } else {
      progressBarWidth = percentage;
      percentage += '%';
    }

    return (
      <div>
        <div className={progressClasses}>
          <span
            className="mailpoet_progress_bar"
            style={{ width: `${progressBarWidth}%` }}
          />
          <span className="mailpoet_progress_label">
            { percentage }
          </span>
        </div>
        <p style={{ textAlign: 'center' }}>
          { label }
        </p>
      </div>
    );
  },
};

const trackStatsCTAClicked = function () {
  MailPoet.trackEvent(
    'User has clicked a CTA to view detailed stats',
    { 'MailPoet Free version': window.mailpoet_version }
  );
};

const StatisticsMixin = {
  renderStatistics: function (newsletter, isSent, currentTime) {
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
    Hooks.addFilter('mailpoet_newsletters_listing_stats_before', this.addStatsCTALink);
    params = Hooks.applyFilters('mailpoet_newsletters_listing_stats_before', params, newsletter);

    // welcome emails provide explicit total_sent value
    const totalSent = Number((newsletter.total_sent || newsletter.queue.count_processed));

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

    let content;
    if (totalSent >= minNewslettersSent
      && newsletter.statistics.opened >= minNewsletterOpens
      && !tooEarlyForStats
    ) {
      // display stats with badges
      content = (
        <div className="mailpoet_stats_text">
          <div>
            <span>{ percentageOpenedDisplay }% </span>
            <StatsBadge
              stat="opened"
              rate={percentageOpened}
              tooltipId={`opened-${newsletter.id}`}
            />
          </div>
          <div>
            <span>{ percentageClickedDisplay }% </span>
            <StatsBadge
              stat="clicked"
              rate={percentageClicked}
              tooltipId={`clicked-${newsletter.id}`}
            />
          </div>
          <div>
            <span className="mailpoet_stat_hidden">{ percentageUnsubscribedDisplay }%</span>
          </div>
        </div>
      );
    } else {
      // display simple stats
      content = (
        <div>
          <span className="mailpoet_stats_text">
            { percentageOpenedDisplay }%,
            { ' ' }
            { percentageClickedDisplay }%
            <span className="mailpoet_stat_hidden">
              , { percentageUnsubscribedDisplay }%
            </span>
          </span>
          { tooEarlyForStats && (
            <div className="mailpoet_badge mailpoet_badge_green">
              {MailPoet.I18n.t('checkBackInHours')
                  .replace('%$1d', showStatsTimeout - sentHoursAgo)}
            </div>
          ) }
        </div>
      );
    }

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
            className="mailpoet_stat_link_small"
          >
            {MailPoet.I18n.t('improveThisLinkText')}
          </a>
        </div>
      );
    }

    if (totalSent > 0 && params.link) {
      // wrap content in a link
      if (params.externalLink) {
        return (
          <div>
            <a
              key={`stats-${newsletter.id}`}
              href={params.link}
              onClick={params.onClick || null}
            >
              {content}
            </a>
            {afterContent}
          </div>
        );
      }
      return (
        <div>
          <Link
            key={`stats-${newsletter.id}`}
            to={params.link}
            onClick={params.onClick || null}
          >
            {content}
          </Link>
          {afterContent}
        </div>
      );
    }

    return (
      <div>
        {content}
        {afterContent}
      </div>
    );
  },
  addStatsCTAAction: function (actions) {
    if (window.mailpoet_premium_active) {
      return actions;
    }
    actions.unshift({
      name: 'stats',
      link: function () {
        return (
          <a href={'admin.php?page=mailpoet-premium'} onClick={trackStatsCTAClicked}>
            {MailPoet.I18n.t('statsListingActionTitle')}
          </a>
        );
      },
      display: function (newsletter) {
        // welcome emails provide explicit total_sent value
        const countProcessed = newsletter.queue && newsletter.queue.count_processed;
        return Number(newsletter.total_sent || countProcessed) > 0;
      },
    });
    return actions;
  },
  addStatsCTALink: function (params) {
    if (window.mailpoet_premium_active) {
      return params;
    }
    const newParams = params;
    newParams.link = 'admin.php?page=mailpoet-premium';
    newParams.externalLink = true;
    newParams.onClick = trackStatsCTAClicked;
    return newParams;
  },
};

const MailerMixin = {
  checkMailerStatus: function (state) {
    if (state.meta.mta_log.error && state.meta.mta_log.status === 'paused') {
      MailPoet.Notice.error(
        '',
        { static: true, id: 'mailpoet_mailer_error' }
      );

      ReactDOM.render(
        this.getMailerError(state),
        jQuery('[data-id="mailpoet_mailer_error"]')[0]
      );
    } else {
      MailPoet.Notice.hide('mailpoet_mailer_error');
    }
  },
  getMailerError(state) {
    let mailerErrorNotice;
    const mailerCheckSettingsNotice = ReactStringReplace(
      MailPoet.I18n.t('mailerCheckSettingsNotice'),
      /\[link\](.*?)\[\/link\]/g,
      match => (
        <a href={'?page=mailpoet-settings#mta'} key="check-sending">{ match }</a>
      )
    );
    if (state.meta.mta_log.error.operation === 'send') {
      mailerErrorNotice =
        MailPoet.I18n.t('mailerSendErrorNotice')
          .replace('%$1s', state.meta.mta_method)
          .replace('%$2s', state.meta.mta_log.error.error_message);
    } else {
      mailerErrorNotice =
        MailPoet.I18n.t('mailerConnectionErrorNotice')
          .replace('%$1s', state.meta.mta_log.error.error_message);
    }
    if (state.meta.mta_log.error.error_code) {
      mailerErrorNotice += ` ${MailPoet.I18n.t('mailerErrorCode')
          .replace('%$1s', state.meta.mta_log.error.error_code)}`;
    }
    return (
      <div>
        <p>{ mailerErrorNotice }</p>
        <p>{ mailerCheckSettingsNotice }</p>
        <p>
          <a href="javascript:;"
            className="button"
            onClick={this.resumeMailerSending}
          >{ MailPoet.I18n.t('mailerResumeSendingButton') }</a>
        </p>
      </div>
    );
  },
  resumeMailerSending() {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'mailer',
      action: 'resumeSending',
    }).done(() => {
      MailPoet.Notice.hide('mailpoet_mailer_error');
      MailPoet.Notice.success(MailPoet.I18n.t('mailerSendingResumedNotice'));
      window.mailpoet_listing.forceUpdate();
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    });
  },
};


export { QueueMixin };
export { StatisticsMixin };
export { MailerMixin };
