import { useState } from 'react';
import { MailPoet } from 'mailpoet';
import { Link } from 'react-router-dom';
import parseDate from 'date-fns/parse';
import { APIErrorsNotice } from 'notices/api_errors_notice';
import { Button } from 'common/button/button';
import { NewsletterStatus } from 'common/listings/newsletter_status';
import { withBoundary } from '../../common';
import {
  NewsLetter,
  NewsletterStatus as NewsletterStatusEnum,
} from '../models';
import { confirmAlert } from '../../common/confirm_alert';

type QueueSendingProps = {
  newsletter: NewsLetter;
};

function QueueSending({ newsletter }: QueueSendingProps) {
  const [paused, setPaused] = useState(newsletter.queue.status === 'paused');
  const [errors, setErrors] = useState([]);

  const pauseSending = async () => {
    setErrors([]);
    await MailPoet.Ajax.post({
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

  const requestResume = async () => {
    await MailPoet.Ajax.post({
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

  const resumeSending = async () => {
    setErrors([]);
    await requestResume();
  };

  const confirmAndResumeSending = async () => {
    setErrors([]);
    confirmAlert({
      message: MailPoet.I18n.t('confirmResumingCorruptNewsletter'),
      onConfirm: requestResume,
    });
  };

  return (
    <>
      <APIErrorsNotice errors={errors} />
      {paused && (
        <Button
          dimension="small"
          onClick={
            newsletter.status === NewsletterStatusEnum.Corrupt
              ? confirmAndResumeSending
              : resumeSending
          }
        >
          {MailPoet.I18n.t('resume')}
        </Button>
      )}
      {!paused && (
        <Button dimension="small" onClick={pauseSending}>
          {MailPoet.I18n.t('pause')}
        </Button>
      )}
    </>
  );
}

type QueueStatusProps = {
  newsletter: NewsLetter;
  mailerLog: {
    status: string;
  };
};

function QueueStatus({ newsletter, mailerLog }: QueueStatusProps) {
  const rawNewsletterDate = newsletter.sent_at || newsletter.queue.scheduled_at;
  const newsletterDate = rawNewsletterDate
    ? parseDate(rawNewsletterDate, 'yyyy-MM-dd HH:mm:ss', new Date())
    : undefined;

  const isNewsletterSending =
    newsletter.queue && newsletter.queue.status !== 'scheduled';
  const isMtaPaused = mailerLog.status === 'paused';

  const renderSentNewsletter = (
    <>
      <Link
        to={`/sending-status/${newsletter.id}`}
        data-automation-id={`sending_status_${newsletter.id}`}
      >
        <NewsletterStatus
          processed={parseInt(newsletter.queue.count_processed, 10)}
          scheduledFor={newsletterDate}
          total={parseInt(newsletter.queue.count_total, 10)}
          isPaused={isMtaPaused}
          status={newsletter.status}
          logs={newsletter.logs}
        />
      </Link>
      {newsletter.queue.status !== 'completed' && !isMtaPaused && (
        <QueueSending newsletter={newsletter} />
      )}
    </>
  );

  const renderDraftOrScheduledNewsletter = (
    <NewsletterStatus
      scheduledFor={newsletterDate}
      isPaused={newsletter.queue.status === 'scheduled' && isMtaPaused}
      status={newsletter.status}
      logs={newsletter.logs}
    />
  );

  return (
    <>
      {isNewsletterSending && renderSentNewsletter}
      {!isNewsletterSending && renderDraftOrScheduledNewsletter}
    </>
  );
}

QueueStatus.displayName = 'QueueStatus';
const QueueStatusWithBoundary = withBoundary(QueueStatus);
export { QueueStatusWithBoundary as QueueStatus };
