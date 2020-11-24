import React from 'react';
import Modal from 'common/modal/modal';
import { useSelect } from '@wordpress/data';
import MailPoet from 'mailpoet';

export const Tutorial = () => {
  const url = useSelect(
    (select) => select('mailpoet-form-editor').getTutorialUrl(),
    []
  );

  function onClose() {
    MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'user_flags',
      action: 'set',
      data: { form_editor_tutorial_seen: 1 },
    });
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
