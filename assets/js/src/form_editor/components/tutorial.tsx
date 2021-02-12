import React from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import Modal from 'common/modal/modal';
import Heading from 'common/typography/heading/heading';
import MailPoet from 'mailpoet';

export const Tutorial: React.FunctionComponent = () => {
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

  function onClose(): void {
    tutorialDismissed();
  }

  return (
    <Modal
      isDismissible
      onRequestClose={onClose}
    >
      <div className="mailpoet_drag_and_drop_tutorial" data-automation-id="form-editor-tutorial">
        <Heading level={2}>{MailPoet.I18n.t('tutorialHeading')}</Heading>
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
