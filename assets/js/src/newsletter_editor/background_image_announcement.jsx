import React from 'react';
import MailPoet from 'mailpoet';
import InAppAnnouncement from 'in_app_announcements/in_app_announcement.jsx';

const BackgroundImageAnnouncement = () => {
  const heading = MailPoet.I18n.t('announcementBackgroundImagesHeading')
    .replace('%username%', window.config.currentUserFirstName || window.config.currentUserUsername);
  return (
    <InAppAnnouncement
      validUntil={new Date('2018-10-06')}
      height="700px"
      showOnlyOnceSlug="background_image"
    >
      <div className="mailpoet_in_app_announcement_background_videos">
        <h2>{heading}</h2>
        <p>{MailPoet.I18n.t('announcementBackgroundImagesMessage')}</p>
        <video src={window.config.backgroundImageDemoUrl} controls autoPlay><track kind="captions" /></video>
      </div>
    </InAppAnnouncement>
  );
};

module.exports = BackgroundImageAnnouncement;
