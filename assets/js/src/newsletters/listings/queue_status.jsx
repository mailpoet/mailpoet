import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import APIErrorsNotice from 'notices/api_errors_notice.tsx';
import Button from 'common/button/button';
import NewsletterStatus from 'common/listings/newsletter_status';

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
      {paused && <Button dimension="small" onClick={resumeSending}>{MailPoet.I18n.t('resume')}</Button>}
      {!paused && <Button dimension="small" onClick={pauseSending}>{MailPoet.I18n.t('pause')}</Button>}
    </>
  );
};
QueueSending.propTypes = {
  newsletter: NewsletterPropType.isRequired,
};

const QueueStatus = ({ newsletter, mailerLog }) => {
  let newsletterDate = newsletter.sent_at || newsletter.queue.scheduled_at;
  if (newsletterDate) {
    newsletterDate = new Date(newsletterDate);
  }
  const isNewsletterSending = newsletter.queue && newsletter.queue.status !== 'scheduled';
  const isMtaPaused = mailerLog.status === 'paused';

  const renderSentNewsletter = (
    <>
      <Link to={`/sending-status/${newsletter.id}`} data-automation-id={`sending_status_${newsletter.id}`}>
        <NewsletterStatus
          processed={parseInt(newsletter.queue.count_processed, 10)}
          scheduledFor={newsletterDate}
          total={parseInt(newsletter.queue.count_total, 10)}
          isPaused={isMtaPaused}
        />
      </Link>
      {(newsletter.queue.status !== 'completed' && !isMtaPaused) && <QueueSending newsletter={newsletter} />}
    </>
  );

  const renderDraftOrScheduledNewsletter = (
    <NewsletterStatus
      scheduledFor={newsletterDate}
      isPaused={newsletter.queue.status === 'scheduled' && isMtaPaused}
    />
  );

  return (
    <>
      {isNewsletterSending && renderSentNewsletter}
      {!isNewsletterSending && renderDraftOrScheduledNewsletter}
    </>
  );
};
QueueStatus.propTypes = {
  newsletter: NewsletterPropType.isRequired,
  mailerLog: PropTypes.shape({
    status: PropTypes.string,
  }).isRequired,
};

export default QueueStatus;
