import { useCallback } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import { Modal } from 'common/modal/modal';
import { Heading } from 'common/typography/heading/heading';
import { MailPoet } from 'mailpoet';
import { storeName } from '../store';

export function Tutorial(): JSX.Element {
  const url = useSelect((select) => select(storeName).getTutorialUrl(), []);
  const tutorialSeen = useSelect(
    (select) => select(storeName).getTutorialSeen(),
    [],
  );
  const { tutorialDismissed } = useDispatch(storeName);

  const onClose = useCallback((): void => {
    void tutorialDismissed();
  }, [tutorialDismissed]);

  if (tutorialSeen) {
    return null;
  }

  return (
    <Modal isDismissible onRequestClose={onClose}>
      <div
        className="mailpoet_drag_and_drop_tutorial"
        data-automation-id="form-editor-tutorial"
      >
        <Heading level={2}>{MailPoet.I18n.t('tutorialHeading')}</Heading>
        {/* eslint-disable-next-line jsx-a11y/media-has-caption */}
        <video style={{ height: '640px' }} src={url} controls autoPlay />
      </div>
    </Modal>
  );
}
