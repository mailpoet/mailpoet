import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { Link, withRouter } from 'react-router-dom';
import PropTypes from 'prop-types';

import { Listing } from 'listing/listing.jsx';
import { MailPoet } from 'mailpoet';
import { QueueStatus } from 'newsletters/listings/queue_status';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import {
  addStatsCTAAction,
  checkCronStatus,
  checkMailerStatus,
} from 'newsletters/listings/utils.jsx';
import { SegmentTags } from 'common/tag/tags';
import { withBoundary } from '../../common';

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

const columns = [
  {
    name: 'subject',
    label: __('Subject', 'mailpoet'),
  },
  {
    name: 'status',
    label: __('Status', 'mailpoet'),
  },
  {
    name: 'segments',
    label: __('Lists', 'mailpoet'),
  },
  {
    name: 'statistics',
    label: __('Clicked, Opened', 'mailpoet'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'sent_at',
    label: __('Sent on', 'mailpoet'),
    sortable: true,
  },
];

const messages = {
  onNoItemsFound: (group, search) =>
    search
      ? __('No emails found.', 'mailpoet')
      : __(
          "Nothing here yet! But, don't fret - there's no reason to get upset. Pretty soon, you’ll be sending emails faster than a turbo-jet.",
          'mailpoet',
        ),
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 email was moved to the trash.', 'mailpoet');
    } else {
      message = __('%1$d emails were moved to the trash.', 'mailpoet').replace(
        '%1$d',
        count.toLocaleString(),
      );
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 email was permanently deleted.', 'mailpoet');
    } else {
      message = __('%1$d emails were permanently deleted.', 'mailpoet').replace(
        '%1$d',
        count.toLocaleString(),
      );
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 email has been restored from the Trash.', 'mailpoet');
    } else {
      message = __(
        '%1$d emails have been restored from the Trash.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
};

const bulkActions = [
  {
    name: 'trash',
    label: __('Move to trash', 'mailpoet'),
    onSuccess: messages.onTrash,
  },
];

const newsletterActions = addStatsCTAAction([
  {
    name: 'view',
    link: function link(newsletter) {
      return (
        <a
          href={newsletter.preview_url}
          target="_blank"
          rel="noopener noreferrer"
        >
          {__('Preview', 'mailpoet')}
        </a>
      );
    },
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
  },
]);

const renderItem = (newsletter, actions, meta) => {
  const rowClasses = classnames(
    'manage-column',
    'column-primary',
    'has-row-actions',
  );

  return (
    <>
      <td className={rowClasses}>
        <strong>
          <a
            href={newsletter.preview_url}
            target="_blank"
            rel="noopener noreferrer"
          >
            {newsletter.queue.newsletter_rendered_subject || newsletter.subject}
          </a>
        </strong>
        {actions}
      </td>
      <td
        className="column mailpoet-listing-status-column"
        data-colname={__('Status', 'mailpoet')}
      >
        <QueueStatus newsletter={newsletter} mailerLog={meta.mta_log} />
      </td>
      <td
        className="column mailpoet-hide-on-mobile"
        data-colname={__('Lists', 'mailpoet')}
      >
        <SegmentTags segments={newsletter.segments} dimension="large" />
      </td>
      {mailpoetTrackingEnabled === true ? (
        <td
          className="column mailpoet-listing-stats-column"
          data-colname={__('Clicked, Opened', 'mailpoet')}
        >
          <Statistics newsletter={newsletter} currentTime={meta.current_time} />
        </td>
      ) : null}
      <td
        className="column-date mailpoet-hide-on-mobile"
        data-colname={__('Sent on', 'mailpoet')}
      >
        {newsletter.sent_at ? (
          <>
            {MailPoet.Date.short(newsletter.sent_at)}
            <br />
            {MailPoet.Date.time(newsletter.sent_at)}
          </>
        ) : null}
      </td>
    </>
  );
};

function NewsletterListNotificationHistoryComponent(props) {
  return (
    <>
      <Link
        className="mailpoet-button button button-secondary button-small"
        to="/notification"
      >
        {__('Back to Post notifications', 'mailpoet')}
      </Link>

      <Listing
        limit={window.mailpoet_listing_per_page}
        location={props.location}
        params={{
          ...props.match.params,
          parentId: props.parentId,
        }}
        endpoint="newsletters"
        type="notification_history"
        base_url="notification/history/:parentId"
        onRenderItem={renderItem}
        columns={columns}
        messages={messages}
        item_actions={newsletterActions}
        bulk_actions={bulkActions}
        auto_refresh
        sort_by="sent_at"
        sort_order="desc"
        afterGetItems={(state) => {
          checkMailerStatus(state);
          checkCronStatus(state);
        }}
      />
    </>
  );
}

NewsletterListNotificationHistoryComponent.propTypes = {
  parentId: PropTypes.string.isRequired,
  location: PropTypes.shape({
    pathname: PropTypes.string,
  }).isRequired,
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.node,
    }).isRequired,
  }).isRequired,
};
NewsletterListNotificationHistoryComponent.displayName =
  'NewsletterListNotificationHistory';
export const NewsletterListNotificationHistory = withRouter(
  withBoundary(NewsletterListNotificationHistoryComponent),
);
