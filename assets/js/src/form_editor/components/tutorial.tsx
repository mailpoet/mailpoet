import React from 'react';
import Modal from 'common/modal/modal';
import { useSelect, useDispatch } from '@wordpress/data';
import MailPoet from 'mailpoet';

export const Tutorial = () => {
  const url = useSelect(
    (select) => select('mailpoet-form-editor').getTutorialUrl(),
    []
  );
  const tutorialSeen = useSelect(
    (select) => select('mailpoet-form-editor').getTutorialSeen(),
    []
  );
  const { tutorialDismissed } = useDispatch('mailpoet-form-editor');

  if (tutorialSeen) {
    return null;
  }

  const installedAtDate = new Date(MailPoet.installedAt);
  const sevenDaysAgo = new Date();
  sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
  if (installedAtDate < sevenDaysAgo) {
    return null; // only show to new users
  }

  function onClose() {
    tutorialDismissed();
  }

  return (
    <Modal
      isDismissible
      onRequestClose={onClose}
    >
      <div className="mailpoet_drag_and_drop_tutorial">
        {/* eslint-disable-next-line jsx-a11y/media-has-caption */}
        <video
          style={{ height: '640px' }}
          src={url}
          controls
          autoPlay
        />
      </div>
    </Modal>
  );
};
