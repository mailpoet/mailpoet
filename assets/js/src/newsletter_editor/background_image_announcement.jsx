import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import InAppAnnouncement from 'in_app_announcements/in_app_announcement.jsx';

const BackgroundImageAnnouncement = props => (
  <InAppAnnouncement
    validUntil={new Date('2018-10-06')}
    height="700px"
    showOnlyOnceSlug="background_image"
  >
    <div className="mailpoet_in_app_announcement_background_videos">
      <h2>
        {MailPoet.I18n.t('announcementBackgroundImagesHeading').replace('%username%', props.username)}
      </h2>
      <p>{MailPoet.I18n.t('announcementBackgroundImagesMessage')}</p>
      <video src={props.videoUrl} controls autoPlay><track kind="captions" /></video>
    </div>
  </InAppAnnouncement>
);

BackgroundImageAnnouncement.propTypes = {
  username: PropTypes.string.isRequired,
  videoUrl: PropTypes.string.isRequired,
};

export default BackgroundImageAnnouncement;
