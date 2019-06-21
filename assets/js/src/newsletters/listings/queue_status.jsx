import React from 'react';
import _ from 'underscore';
import jQuery from 'jquery';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { Link } from 'react-router-dom';

const pauseSending = (newsletter) => {
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
};

const resumeSending = (newsletter) => {
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
};

const QueueStatus = ({ newsletter, mailerLog }) => {
  if (!newsletter.queue) {
    return (
      <span>{MailPoet.I18n.t('notSentYet')}</span>
    );
  }
  if (mailerLog.status === 'paused' && newsletter.queue.status !== 'completed') {
    return (
      <span>{MailPoet.I18n.t('paused')}</span>
    );
  }
  if (newsletter.queue.status === 'scheduled') {
    return (
      <span>
        {MailPoet.I18n.t('scheduledFor')}
        {' '}
        {MailPoet.Date.format(newsletter.queue.scheduled_at)}
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
      <Link to={`/sending-status/${newsletter.id}`} data-automation-id={`sending_status_${newsletter.id}`}>
        <span>
          {
            MailPoet.I18n.t('newsletterQueueCompleted')
              .replace('%$1d', parseInt(newsletter.queue.count_processed, 10).toLocaleString())
              .replace('%$2d', parseInt(newsletter.queue.count_total, 10).toLocaleString())
          }
        </span>
      </Link>
    );
  } else {
    const resumeSendingClick = _.partial(resumeSending, newsletter);
    const pauseSendingClick = _.partial(pauseSending, newsletter);
    label = (
      <span>
        {parseInt(newsletter.queue.count_processed, 10).toLocaleString()}
        /
        {parseInt(newsletter.queue.count_total, 10).toLocaleString()}
        &nbsp;&nbsp;
        <a
          id={`resume_${newsletter.id}`}
          className="button"
          style={{
            display: (newsletter.queue.status === 'paused')
              ? 'inline-block' : 'none',
          }}
          href="javascript:;"
          onClick={resumeSendingClick}
        >
          {MailPoet.I18n.t('resume')}
        </a>
        <a
          id={`pause_${newsletter.id}`}
          className="button mailpoet_pause"
          style={{
            display: (newsletter.queue.status === null)
              ? 'inline-block' : 'none',
          }}
          href="javascript:;"
          onClick={pauseSendingClick}
        >
          {MailPoet.I18n.t('pause')}
        </a>
      </span>
    );
  }

  let progressBarWidth = 0;
  if (Number.isFinite(percentage)) {
    progressBarWidth = percentage;
    percentage += '%';
  } else {
    percentage = MailPoet.I18n.t('noSubscribers');
  }

  return (
    <div>
      <div className={progressClasses}>
        <span
          className="mailpoet_progress_bar"
          style={{ width: `${progressBarWidth}%` }}
        />
        <span className="mailpoet_progress_label">
          {percentage}
        </span>
      </div>
      <p style={{ textAlign: 'center' }}>
        {label}
      </p>
    </div>
  );
};
QueueStatus.propTypes = {
  newsletter: PropTypes.shape({
    id: PropTypes.number.isRequired,
    queue: PropTypes.shape({
      status: PropTypes.string,
      count_processed: PropTypes.string.isRequired,
      count_total: PropTypes.string.isRequired,
      scheduled_at: PropTypes.string,
    }),
  }).isRequired,
  mailerLog: PropTypes.shape({
    status: PropTypes.string,
  }).isRequired,
};

export default QueueStatus;
