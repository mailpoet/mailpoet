import React from 'react';
import { Link, withRouter } from 'react-router-dom';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

import Listing from 'listing/listing.jsx';
import QueueStatus from 'newsletters/listings/queue_status.jsx';
import Statistics from 'newsletters/listings/statistics.jsx';
import {
  addStatsCTAAction,
  checkCronStatus,
  checkMailerStatus,
} from 'newsletters/listings/utils.jsx';

const mailpoetTrackingEnabled = (!!(window.mailpoet_tracking_enabled));

const columns = [
  {
    name: 'subject',
    label: MailPoet.I18n.t('subject'),
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('lists'),
  },
  {
    name: 'statistics',
    label: MailPoet.I18n.t('statistics'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'sent_at',
    label: MailPoet.I18n.t('sentOn'),
  },
];

const messages = {
  onNoItemsFound: (group, search) => MailPoet.I18n.t(search ? 'noItemsFound' : 'emptyListing'),
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersTrashed')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersDeleted')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersRestored')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
};

const bulkActions = [
  {
    name: 'trash',
    label: MailPoet.I18n.t('moveToTrash'),
    onSuccess: messages.onTrash,
  },
];

const newsletterActions = addStatsCTAAction([
  {
    name: 'view',
    link: function link(newsletter) {
      return (
        <a href={newsletter.preview_url} target="_blank" rel="noopener noreferrer">
          {MailPoet.I18n.t('preview')}
        </a>
      );
    },
  },
  {
    name: 'trash',
  },
]);

const renderItem = (newsletter, actions, meta) => {
  const rowClasses = classNames(
    'manage-column',
    'column-primary',
    'has-row-actions'
  );

  const segments = newsletter.segments.map((segment) => segment.name).join(', ');

  return (
    <>
      <td className={rowClasses}>
        <strong>
          <a
            href={newsletter.preview_url}
            target="_blank"
            rel="noopener noreferrer"
          >
            { newsletter.queue.newsletter_rendered_subject || newsletter.subject }
          </a>
        </strong>
        { actions }
      </td>
      <td className="column" data-colname={MailPoet.I18n.t('status')}>
        <QueueStatus newsletter={newsletter} mailerLog={meta.mta_log} />
      </td>
      <td className="column" data-colname={MailPoet.I18n.t('lists')}>
        { segments }
      </td>
      { (mailpoetTrackingEnabled === true) ? (
        <td className="column" data-colname={MailPoet.I18n.t('statistics')}>
          <Statistics newsletter={newsletter} currentTime={meta.current_time} />
        </td>
      ) : null }
      <td className="column-date" data-colname={MailPoet.I18n.t('sentOn')}>
        { (newsletter.sent_at) ? MailPoet.Date.format(newsletter.sent_at) : MailPoet.I18n.t('notSentYet') }
      </td>
    </>
  );
};

const NewsletterListNotificationHistory = (props) => (
  <>
    <Link
      className="page-title-action"
      to="/notification"
    >
      {MailPoet.I18n.t('backToPostNotifications')}
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

NewsletterListNotificationHistory.propTypes = {
  parentId: PropTypes.string.isRequired,
  location: PropTypes.shape({
    pathname: PropTypes.string,
  }).isRequired,
  match: PropTypes.shape({
    params: PropTypes.object,
  }).isRequired,
};

export default withRouter(NewsletterListNotificationHistory);
