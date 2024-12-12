import classnames from 'classnames';

import { __ } from '@wordpress/i18n';
import { QueueStatus } from 'newsletters/listings/queue-status';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import { SegmentTags, FilterSegmentTag } from '../../common/tag/tags';
import { ErrorBoundary  } from '../../common';

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;


const confirmEdit = (newsletter) => {
  let editorHref = `?page=mailpoet-newsletter-editor&id=${newsletter.id}`;
  if (
    MailPoet.FeaturesController.isSupported('gutenberg_email_editor') &&
    newsletter.wp_post_id
  ) {
    editorHref = MailPoet.getBlockEmailEditorUrl(newsletter.wp_post_id);
  }

  if (
    !newsletter.queue ||
    newsletter.status !== 'sending' ||
    newsletter.queue.status !== null
  ) {
    window.location.href = editorHref;
  } else {
    confirmAlert({
      message: __(
        'Sending is in progress. Do you want to pause sending and edit the newsletter?',
        'mailpoet',
      ),
      onConfirm: () => {
        window.location.href = `${editorHref}&pauseConfirmed=yes`;
      },
    });
  }
};


export function getRow(newsletter, meta) {
  const subject = newsletter.queue.newsletter_rendered_subject || newsletter.subject;
  const rowClasses = classnames(
    'manage-column',
    'column-primary',
    'has-row-actions',
  );


    return [
      {
        display: (
          <div className={rowClasses}>
            <a
              className="mailpoet-listing-title"
              href="#"
              onClick={(event) => {
                event.preventDefault();
                confirmEdit(newsletter);
              }}
            >
              {newsletter.campaign_name ? (
                <div>
                  {newsletter.campaign_name} <br />
                  <span className="mailpoet-listing-subtitle">{subject}</span>
                </div>
              ) : (
                subject
              )}
            </a>
          </div>
        ),
        value: newsletter.campaign_name || subject,
      },
        { display: <QueueStatus newsletter={newsletter} mailerLog={meta.mta_log} />, value: newsletter.status },
        { display: <div
            className="column mailpoet-hide-on-mobile"
            data-colname={__('Lists', 'mailpoet')}>
            <ErrorBoundary>
              <SegmentTags segments={newsletter.segments} dimension="large" />
              <FilterSegmentTag newsletter={newsletter} dimension="large" />
            </ErrorBoundary>
          </div>, value: newsletter.segment_ids },
        { display: <Statistics newsletter={newsletter} currentTime={meta.current_time} />, value: newsletter.statistics },
        { display: newsletter.sent_at ? newsletter.sent_at : 'Not sent', value: newsletter.sent_at || '' },
      ];
}
