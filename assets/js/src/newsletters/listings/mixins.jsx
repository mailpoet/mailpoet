import React from 'react'
import ReactDOM from 'react-dom'
import MailPoet from 'mailpoet'
import classNames from 'classnames'
import jQuery from 'jquery'
import ListingNotices from 'newsletters/listings/notices.jsx'

const _QueueMixin = {
  pauseSending: function(newsletter) {
    MailPoet.Ajax.post({
      endpoint: 'sendingQueue',
      action: 'pause',
      data: {
        newsletter_id: newsletter.id
      }
    }).done(function() {
      jQuery('#resume_'+newsletter.id).show();
      jQuery('#pause_'+newsletter.id).hide();
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(function(error) { return error.message; }),
          { scroll: true }
        );
      }
    });
  },
  resumeSending: function(newsletter) {
    MailPoet.Ajax.post({
      endpoint: 'sendingQueue',
      action: 'resume',
      data: {
        newsletter_id: newsletter.id
      }
    }).done(function() {
      jQuery('#pause_'+newsletter.id).show();
      jQuery('#resume_'+newsletter.id).hide();
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(function(error) { return error.message; }),
          { scroll: true }
        );
      }
    });
  },
  renderQueueStatus: function(newsletter, mailer_log) {
    if (!newsletter.queue) {
      return (
        <span>{MailPoet.I18n.t('notSentYet')}</span>
      );
    } else if (mailer_log.status === 'paused') {
      return (
        <span>{MailPoet.I18n.t('paused')}</span>
      )
    } else {
      if (newsletter.queue.status === 'scheduled') {
        return (
          <span>
            { MailPoet.I18n.t('scheduledFor') } { MailPoet.Date.format(newsletter.queue.scheduled_at) }
          </span>
        )
      }
      const progressClasses = classNames(
        'mailpoet_progress',
        { 'mailpoet_progress_complete': newsletter.queue.status === 'completed'}
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
              .replace(
                "%$1d",
                newsletter.queue.count_processed - newsletter.queue.count_failed
              )
              .replace(
                "%$2d",
                newsletter.queue.count_total
              )
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
                style={ { width: progress_bar_width + "%"} }
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
  renderStatistics: function(newsletter) {
    if (
      newsletter.statistics
      && newsletter.queue
      && newsletter.queue.status !== 'scheduled'
    ) {
      const total_sent = ~~(newsletter.queue.count_processed);

      let percentage_clicked = 0;
      let percentage_opened = 0;
      let percentage_unsubscribed = 0;

      if (total_sent > 0) {
        percentage_clicked = Math.round(
          (~~(newsletter.statistics.clicked) * 100) / total_sent
        );
        percentage_opened = Math.round(
          (~~(newsletter.statistics.opened) * 100) / total_sent
        );
        percentage_unsubscribed = Math.round(
          (~~(newsletter.statistics.unsubscribed) * 100) / total_sent
        );
      }

      return (
        <span>
          { percentage_opened }%, { percentage_clicked }%, { percentage_unsubscribed }%
        </span>
      );
    } else {
      return (
        <span>{MailPoet.I18n.t('notSentYet')}</span>
      );
    }
  }
}

const _MailerMixin = {
  checkMailerStatus: function(state) {
    if (state.meta.mta_log.error) {
      MailPoet.Notice.error(
        '',
        { static: true, id: 'mailpoet_mailer_error' }
      );

      ReactDOM.render((
        <ListingNotices
          mta_log={ state.meta.mta_log }
          mta_method={ state.meta.mta_method }
        />
      ), jQuery('[data-id="mailpoet_mailer_error"]')[0]);

    } else {
      MailPoet.Notice.hide('mailpoet_mailer_error');
    }
  }
}

export { _QueueMixin as QueueMixin };
export { _StatisticsMixin as StatisticsMixin };
export { _MailerMixin as MailerMixin };