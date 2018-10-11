import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import moment from 'moment';
import InAppAnnouncement from 'in_app_announcements/in_app_announcement.jsx';

const NewSubscriberNotificationAnnouncement = props => (
  <InAppAnnouncement
    validUntil={moment(props.installedAt).add(3, 'months').toDate()}
    height="700px"
    showOnlyOnceSlug="new_subscriber_notification"
    showToNewUser={false}
  >
    <div className="new_subscriber_notification_announcement">
      <h1>{MailPoet.I18n.t('announcementHeader')}</h1>
      <img src={props.imageUrl} width="600px" height="460px" alt="" />
      <p>{MailPoet.I18n.t('announcementParagraph')}</p>
    </div>
  </InAppAnnouncement>
);

NewSubscriberNotificationAnnouncement.propTypes = {
  installedAt: PropTypes.string.isRequired,
  imageUrl: PropTypes.string.isRequired,
};

module.exports = NewSubscriberNotificationAnnouncement;
