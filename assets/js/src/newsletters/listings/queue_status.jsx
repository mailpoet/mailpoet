import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { Link } from 'react-router-dom';
import APIErrorsNotice from 'notices/api_errors_notice.tsx';

const QueuePropType = PropTypes.shape({
  status: PropTypes.string,
  count_processed: PropTypes.string.isRequired,
  count_total: PropTypes.string.isRequired,
  scheduled_at: PropTypes.string,
});

const NewsletterPropType = PropTypes.shape({
  id: PropTypes.number.isRequired,
  queue: PropTypes.oneOfType([
    QueuePropType,
    PropTypes.bool,
  ]),
});

const QueueSendingProgress = ({ queue }) => {
  const progressClasses = classNames(
    'mailpoet_progress',
    { mailpoet_progress_complete: queue.status === 'completed' }
  );
    // calculate percentage done
  let percentage = Math.round(
    (queue.count_processed * 100) / (queue.count_total)
  );
  let progressBarWidth = 0;
  if (Number.isFinite(percentage)) {
    progressBarWidth = percentage;
    percentage += '%';
  } else {
    percentage = MailPoet.I18n.t('noSubscribers');
  }

  return (
    <div className={progressClasses}>
      <span
        className="mailpoet_progress_bar"
        style={{ width: `${progressBarWidth}%` }}
      />
      <span className="mailpoet_progress_label">
        {percentage}
      </span>
    </div>
  );
};
QueueSendingProgress.propTypes = {
  queue: QueuePropType.isRequired,
};

const QueueCompleted = ({ newsletter }) => (
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
QueueCompleted.propTypes = {
  newsletter: NewsletterPropType.isRequired,
};

const LinkButton = ({ className, onClick, children }) => (
  <a
    className={classNames('button', className)}
    style={{ display: 'inline-block' }}
    href="#"
    onClick={(event) => {
      event.preventDefault();
      onClick(event);
    }}
  >
    {children}
  </a>
);
LinkButton.propTypes = {
  className: PropTypes.string,
  onClick: PropTypes.func.isRequired,
  children: PropTypes.string.isRequired,
};
LinkButton.defaultProps = {
  className: '',
};

const QueueSending = ({ newsletter }) => {
  const [paused, setPaused] = React.useState(newsletter.queue.status === 'paused');
  const [errors, setErrors] = React.useState([]);

  const pauseSending = () => {
    setErrors([]);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'sendingQueue',
      action: 'pause',
      data: {
        newsletter_id: newsletter.id,
      },
    })
      .done(() => setPaused(true))
      .fail((response) => setErrors(response.errors));
  };

  const resumeSending = () => {
    setErrors([]);
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'sendingQueue',
      action: 'resume',
      data: {
        newsletter_id: newsletter.id,
      },
    })
      .done(() => setPaused(false))
      .fail((response) => setErrors(response.errors));
  };

  return (
    <>
      <APIErrorsNotice errors={errors} />
      <span>
        {parseInt(newsletter.queue.count_processed, 10).toLocaleString()}
        /
        {parseInt(newsletter.queue.count_total, 10).toLocaleString()}
        &nbsp;&nbsp;
        {paused && <LinkButton onClick={resumeSending}>{MailPoet.I18n.t('resume')}</LinkButton>}
        {!paused && (
        <LinkButton
          className="mailpoet_pause"
          onClick={pauseSending}
        >
          {MailPoet.I18n.t('pause')}
        </LinkButton>
        )}
      </span>
    </>
  );
};
QueueSending.propTypes = {
  newsletter: NewsletterPropType.isRequired,
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

  return (
    <div>
      <QueueSendingProgress queue={newsletter.queue} />
      <p style={{ textAlign: 'center' }}>
        {newsletter.queue.status === 'completed' && <QueueCompleted newsletter={newsletter} />}
        {newsletter.queue.status !== 'completed' && <QueueSending newsletter={newsletter} />}
      </p>
    </div>
  );
};
QueueStatus.propTypes = {
  newsletter: NewsletterPropType.isRequired,
  mailerLog: PropTypes.shape({
    status: PropTypes.string,
  }).isRequired,
};

export default QueueStatus;
