import React from 'react';
import { Link } from 'react-router-dom';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

import Listing from 'listing/listing.jsx';
import ListingTabs from 'newsletters/listings/tabs.jsx';
import ListingHeading from 'newsletters/listings/heading.jsx';
import FeatureAnnouncement from 'announcements/feature_announcement.jsx';

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

let newsletterActions = [
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
];
newsletterActions = addStatsCTAAction(newsletterActions);

class NewsletterListNotificationHistory extends React.Component {
  static displayName = 'NewsletterListNotificationHistory';

  static propTypes = {
    location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
    match: PropTypes.shape({
      params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
    }).isRequired,
  };

  renderItem = (newsletter, actions, meta) => {
    const rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    const segments = newsletter.segments.map((segment) => segment.name).join(', ');

    return (
      <div>
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
      </div>
    );
  };

  render() {
    return (
      <div>
        <ListingHeading />

        <FeatureAnnouncement hasNews={window.mailpoet_feature_announcement_has_news} />

        <ListingTabs tab="notification" />

        <Link
          className="page-title-action"
          to="/notification"
        >
          {MailPoet.I18n.t('backToPostNotifications')}
        </Link>

        <Listing
          limit={window.mailpoet_listing_per_page}
          location={this.props.location}
          params={this.props.match.params}
          endpoint="newsletters"
          type="notification_history"
          base_url="notification/history/:parent_id"
          onRenderItem={this.renderItem}
          columns={columns}
          item_actions={newsletterActions}
          auto_refresh
          sort_by="sent_at"
          sort_order="desc"
          afterGetItems={(state) => { checkMailerStatus(state); checkCronStatus(state); }}
        />
      </div>
    );
  }
}

export default NewsletterListNotificationHistory;
