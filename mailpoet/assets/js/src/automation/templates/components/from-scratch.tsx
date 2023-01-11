import { Dispatch, useCallback, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Hooks } from 'wp-js-hooks';
import { Icon, plusCircleFilled } from '@wordpress/icons';
import { PremiumModal } from '../../../common/premium_modal';
import { Notice } from '../../../notices/notice';
import { FromScratchHookType } from '../../types/filters';

type FromScratchPremiumModalProps = {
  showModal: boolean;
  onClose: () => void;
};

function FromScratchPremiumModal({
  showModal,
  onClose,
}: FromScratchPremiumModalProps): JSX.Element | null {
  if (!showModal) {
    return null;
  }
  return (
    <PremiumModal
      onRequestClose={onClose}
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
  const fromScratchCallback: FromScratchHookType = Hooks.applyFilters(
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
  const [isBusy, setIsBusy] = useState(false);
  const onClickScratchButton = useCallback(() => {
    fromScratchHook(() => {
      setShowModal(true);
    }, errorHandler);
  }, []);

  const premiumClose = () => {
    setShowModal(false);
    setIsBusy(false);
  };
  return (
    <>
      {error && (
        <Notice type="error" closable timeout={false}>
          <p>{error}</p>
        </Notice>
      )}
      <Button
        variant="secondary"
        isBusy={isBusy}
        disabled={isBusy}
        onClick={() => {
          setIsBusy(true);
          onClickScratchButton();
        }}
      >
        {__('From scratch', 'mailpoet')}
      </Button>
      <FromScratchPremiumModal showModal={showModal} onClose={premiumClose} />
    </>
  );
}

export function FromScratchListItem(): JSX.Element {
  const [showModal, setShowModal] = useState(false);
  const [isBusy, setIsBusy] = useState(false);
  const [error, errorHandler] = useState(null);
  const onClickScratchButton = useCallback(() => {
    fromScratchHook(() => {
      setShowModal(true);
    }, errorHandler);
  }, []);

  const premiumClose = () => {
    setShowModal(false);
    setIsBusy(false);
  };

  return (
    <li className="mailpoet-automation-template-list-item mailpoet-automation-from-scratch">
      {error && (
        <Notice type="error" closable timeout={false}>
          <p>{error}</p>
        </Notice>
      )}
      <Button
        isBusy={isBusy}
        disabled={isBusy}
        onClick={() => {
          setIsBusy(true);
          onClickScratchButton();
        }}
      >
        <Icon width="50px" height="50px" icon={plusCircleFilled} />
        {__('Create from scratch', 'mailpoet')}
      </Button>
      <FromScratchPremiumModal showModal={showModal} onClose={premiumClose} />
    </li>
  );
}
