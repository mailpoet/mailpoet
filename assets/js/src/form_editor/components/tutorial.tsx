import React from 'react';
import Modal from 'common/modal/modal';
import { useSelect, useDispatch } from '@wordpress/data';

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

  function onClose() {
    tutorialDismissed();
  }

  return (
    <Modal
      isDismissible
      onRequestClose={onClose}
    >
      <div className="mailpoet_drag_and_drop_tutorial" data-automation-id="form-editor-tutorial">
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
