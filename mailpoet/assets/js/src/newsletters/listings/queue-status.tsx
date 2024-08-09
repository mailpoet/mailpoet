import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Link } from 'react-router-dom';
import { APIErrorsNotice } from 'notices/api-errors-notice';
import { Button } from 'common/button/button';
import { NewsletterStatus } from 'common/listings';
import { confirmAlert, withBoundary } from 'common';
import {
  NewsLetter,
  NewsletterStatus as NewsletterStatusEnum,
} from 'common/newsletter';

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
    confirmAlert({
      message: __(
        'There was an issue sending this email before. Please confirm the problems are fixed to proceed.',
        'mailpoet',
      ),
      onConfirm: resumeSending,
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
          {__('Resume', 'mailpoet')}
        </Button>
      )}
      {!paused && (
        <Button dimension="small" onClick={pauseSending}>
          {__('Pause', 'mailpoet')}
        </Button>
      )}
    </>
  );
}

function CancelledNewsletter() {
  return (
    <div className="mailpoet-listing-stats-too-early">
      <a
        href="admin.php?page=mailpoet-help#/systemStatus"
        className="mailpoet-tag mailpoet-tag-inverted mailpoet-tag-unknown"
      >
        {__('Cancelled', 'mailpoet')}
      </a>
    </div>
  );
}

type QueueStatusProps = {
  newsletter: NewsLetter;
  mailerLog: {
    status: string;
  };
};

function QueueStatus({ newsletter, mailerLog }: QueueStatusProps) {
  const newsletterDate = newsletter.sent_at || newsletter.queue.scheduled_at;
  const isNewsletterCancelled =
    newsletter.queue && newsletter.queue.status === 'cancelled';
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
      {isNewsletterCancelled && <CancelledNewsletter />}
      {newsletter.queue.status !== 'completed' &&
        !isNewsletterCancelled &&
        !isMtaPaused && <QueueSending newsletter={newsletter} />}
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
