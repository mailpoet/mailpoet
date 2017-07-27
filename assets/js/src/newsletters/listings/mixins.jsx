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

const _QueueMixin = {
  pauseSending: function (newsletter) {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'sendingQueue',
      action: 'pause',
      data: {
        newsletter_id: newsletter.id
      }
    }).done(() => {
      jQuery('#resume_'+newsletter.id).show();
      jQuery('#pause_'+newsletter.id).hide();
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => { return error.message; }),
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
        newsletter_id: newsletter.id
      }
    }).done(() => {
      jQuery('#pause_'+newsletter.id).show();
      jQuery('#resume_'+newsletter.id).hide();
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => { return error.message; }),
          { scroll: true }
        );
      }
    });
  },
  renderQueueStatus: function (newsletter, mailer_log) {
    if (!newsletter.queue) {
      return (
        <span>{MailPoet.I18n.t('notSentYet')}</span>
      );
    } else if (mailer_log.status === 'paused' && newsletter.queue.status !== 'completed') {
      return (
        <span>{MailPoet.I18n.t('paused')}</span>
      );
    } else {
      if (newsletter.queue.status === 'scheduled') {
        return (
          <span>
            { MailPoet.I18n.t('scheduledFor') } { MailPoet.Date.format(newsletter.queue.scheduled_at) }
          </span>
        );
      }
      const progressClasses = classNames(
        'mailpoet_progress',
        { 'mailpoet_progress_complete': newsletter.queue.status === 'completed' }
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
              .replace("%$1d", newsletter.queue.count_processed)
              .replace("%$2d", newsletter.queue.count_total)
            }
          </span>
        );
      } else {
        label = (
          <span>
            { newsletter.queue.count_processed } / { newsletter.queue.count_total }
            &nbsp;&nbsp;
            <a
              id={ 'resume_'+newsletter.id }
              className="button"
              style={{ display: (newsletter.queue.status === 'paused')
                ? 'inline-block': 'none' }}
              href="javascript:;"
              onClick={ this.resumeSending.bind(null, newsletter) }
            >{MailPoet.I18n.t('resume')}</a>
            <a
              id={ 'pause_'+newsletter.id }
              className="button mailpoet_pause"
              style={{ display: (newsletter.queue.status === null)
                  ? 'inline-block': 'none' }}
              href="javascript:;"
              onClick={ this.pauseSending.bind(null, newsletter) }
            >{MailPoet.I18n.t('pause')}</a>
          </span>
        );
      }

      let progress_bar_width = 0;

      if (isNaN(percentage)) {
        percentage = MailPoet.I18n.t('noSubscribers');
      } else {
        progress_bar_width = percentage;
        percentage += "%";
      }

      return (
        <div>
          <div className={ progressClasses }>
              <span
                className="mailpoet_progress_bar"
                style={ { width: progress_bar_width + "%" } }
              ></span>
              <span className="mailpoet_progress_label">
                { percentage }
              </span>
          </div>
          <p style={{ textAlign:'center' }}>
            { label }
          </p>
        </div>
      );
    }
  },
};

const _StatisticsMixin = {
  renderStatistics: function (newsletter, is_sent, current_time) {
    if (is_sent === undefined) {
      // condition for standard and post notification listings
      is_sent = newsletter.statistics
        && newsletter.queue
        && newsletter.queue.status !== 'scheduled';
    }
    if (!is_sent) {
      return (
        <span>{MailPoet.I18n.t('notSentYet')}</span>
      );
    }

    let params = {};
    params = Hooks.applyFilters('mailpoet_newsletters_listing_stats_before', params, newsletter);

    // welcome emails provide explicit total_sent value
    const total_sent = ~~(newsletter.total_sent || newsletter.queue.count_processed);

    let percentage_clicked = 0;
    let percentage_opened = 0;
    let percentage_unsubscribed = 0;

    if (total_sent > 0) {
      percentage_clicked = (newsletter.statistics.clicked * 100) / total_sent;
      percentage_opened = (newsletter.statistics.opened * 100) / total_sent;
      percentage_unsubscribed = (newsletter.statistics.unsubscribed * 100) / total_sent;
    }

    // format to 1 decimal place
    const percentage_clicked_display = MailPoet.Num.toLocaleFixed(percentage_clicked, 1);
    const percentage_opened_display = MailPoet.Num.toLocaleFixed(percentage_opened, 1);
    const percentage_unsubscribed_display = MailPoet.Num.toLocaleFixed(percentage_unsubscribed, 1);

    let show_stats_timeout,
      newsletter_date,
      sent_hours_ago,
      too_early_for_stats,
      show_kb_link;
    if (current_time !== undefined) {
      // standard emails and post notifications:
      // display green box for newsletters that were just sent
      show_stats_timeout = 6; // in hours
      newsletter_date = newsletter.queue.scheduled_at || newsletter.queue.created_at;
      sent_hours_ago = moment(current_time).diff(moment(newsletter_date), 'hours');
      too_early_for_stats = sent_hours_ago < show_stats_timeout;
      show_kb_link = true;
    } else {
      // welcome emails: no green box and KB link
      too_early_for_stats = false;
      show_kb_link = false;
    }

    const improveStatsKBLink = 'http://beta.docs.mailpoet.com/article/191-how-to-improve-my-open-and-click-rates';

    // thresholds to display badges
    const min_newsletters_sent = 20;
    const min_newsletter_opens = 5;

    let content;
    if (total_sent >= min_newsletters_sent
      && newsletter.statistics.opened >= min_newsletter_opens
      && !too_early_for_stats
    ) {
      // display stats with badges
      content = (
        <div className="mailpoet_stats_text">
          <div>
            <span>{ percentage_opened_display }% </span>
            <StatsBadge
              stat="opened"
              rate={percentage_opened}
              tooltipId={`opened-${newsletter.id}`}
            />
          </div>
          <div>
            <span>{ percentage_clicked_display }% </span>
            <StatsBadge
              stat="clicked"
              rate={percentage_clicked}
              tooltipId={`clicked-${newsletter.id}`}
            />
          </div>
          <div>
            <span className="mailpoet_stat_hidden">{ percentage_unsubscribed_display }%</span>
          </div>
        </div>
      );
    } else {
      // display simple stats
      content = (
        <div>
          <span className="mailpoet_stats_text">
            { percentage_opened_display }%,
            { " " }
            { percentage_clicked_display }%
            <span className="mailpoet_stat_hidden">
              , { percentage_unsubscribed_display }%
            </span>
          </span>
          { too_early_for_stats && (
            <div className="mailpoet_badge mailpoet_badge_green">
              {MailPoet.I18n.t('checkBackInHours')
                  .replace('%$1d', show_stats_timeout - sent_hours_ago)}
            </div>
          ) }
        </div>
      );
    }

    // thresholds to display bad open rate help
    const max_percentage_opened = 5;
    const min_sent_hours_ago = 24;
    const min_total_sent = 10;

    let after_content;
    if (show_kb_link
      && percentage_opened < max_percentage_opened
      && sent_hours_ago >= min_sent_hours_ago
      && total_sent >= min_total_sent
    ) {
      // help link for bad open rate
      after_content = (
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

    if (total_sent > 0 && params.link) {
      // wrap content in a link
      return (
        <div>
          <Link
            key={ `stats-${newsletter.id}` }
            to={ params.link }
            onClick={ params.onClick || null }
          >
            {content}
          </Link>
          {after_content}
        </div>
      );
    }

    return (
      <div>
        {content}
        {after_content}
      </div>
    );
  }
};

const _MailerMixin = {
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
    let mailer_error_notice;
    const mailer_check_settings_notice = ReactStringReplace(
      MailPoet.I18n.t('mailerCheckSettingsNotice'),
      /\[link\](.*?)\[\/link\]/g,
      match => (
        <a href={`?page=mailpoet-settings#mta`}>{ match }</a>
      )
    );
    if (state.meta.mta_log.error.operation === 'send') {
      mailer_error_notice =
        MailPoet.I18n.t('mailerSendErrorNotice')
          .replace('%$1s', state.meta.mta_method)
          .replace('%$2s', state.meta.mta_log.error.error_message);
    } else {
      mailer_error_notice =
        MailPoet.I18n.t('mailerConnectionErrorNotice')
          .replace('%$1s', state.meta.mta_log.error.error_message);
    }
    return (
      <div>
        <p>{ mailer_error_notice }</p>
        <p>{ mailer_check_settings_notice }</p>
        <p>
          <a href="javascript:;"
             className="button"
             onClick={ this.resumeMailerSending }
          >{ MailPoet.I18n.t('mailerResumeSendingButton') }</a>
        </p>
      </div>
    );
  },
  resumeMailerSending() {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'mailer',
      action: 'resumeSending'
    }).done(() => {
      MailPoet.Notice.hide('mailpoet_mailer_error');
      MailPoet.Notice.success(MailPoet.I18n.t('mailerSendingResumedNotice'));
      window.mailpoet_listing.forceUpdate();
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => { return error.message; }),
          { scroll: true }
        );
      }
    });
  }
};




export { _QueueMixin as QueueMixin };
export { _StatisticsMixin as StatisticsMixin };
export { _MailerMixin as MailerMixin };
