import { Dispatch, SetStateAction, useCallback, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Hooks } from 'wp-js-hooks';
import { Icon, plusCircleFilled } from '@wordpress/icons';
import { PremiumModal } from '../../../common/premium_modal';
import { Notice } from '../../../notices/notice';

type FromScratchPremiumModalProps = {
  showModal: boolean;
  setShowModal: Dispatch<SetStateAction<boolean>>;
};

function FromScratchPremiumModal({
  showModal,
  setShowModal,
}: FromScratchPremiumModalProps): JSX.Element | null {
  if (!showModal) {
    return null;
  }
  return (
    <PremiumModal
      onRequestClose={() => {
        setShowModal(false);
      }}
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_from_scratch',
      }}
    >
      {__('You cannot create automation from scratch.', 'mailpoet')}
    </PremiumModal>
  );
}

function fromScratchHook(callback: () => void, errorHandler: Dispatch<string>) {
  const fromScratchCallback = Hooks.applyFilters(
    'mailpoet.automation.templates.from_scratch_button',
    () => {
      callback();
    },
  );
  fromScratchCallback(errorHandler);
}

export function FromScratchButton(): JSX.Element {
  const [showModal, setShowModal] = useState(false);
  const [error, errorHandler] = useState(null);
  const onClickScratchButton = useCallback(() => {
    fromScratchHook(() => {
      setShowModal(true);
    }, errorHandler);
  }, []);
  return (
    <>
      {error && (
        <Notice type="error" closable timeout={false}>
          <p>{error}</p>
        </Notice>
      )}
      <Button variant="secondary" onClick={() => onClickScratchButton()}>
        {__('From scratch', 'mailpoet')}
      </Button>
      <FromScratchPremiumModal
        showModal={showModal}
        setShowModal={setShowModal}
      />
    </>
  );
}

export function FromScratchListItem(): JSX.Element {
  const [showModal, setShowModal] = useState(false);
  const [error, errorHandler] = useState(null);
  const onClickScratchButton = useCallback(() => {
    fromScratchHook(() => {
      setShowModal(true);
    }, errorHandler);
  }, []);
  return (
    <li className="mailpoet-template-list-item mailpoet-from-scratch">
      {error && (
        <Notice type="error" closable timeout={false}>
          <p>{error}</p>
        </Notice>
      )}
      <Button onClick={() => onClickScratchButton()}>
        <Icon width="50px" height="50px" icon={plusCircleFilled} />
        {__('Create from scratch', 'mailpoet')}
      </Button>
      <FromScratchPremiumModal
        showModal={showModal}
        setShowModal={setShowModal}
      />
    </li>
  );
}
