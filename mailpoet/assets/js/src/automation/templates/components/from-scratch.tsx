import { Dispatch, useCallback, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Hooks } from 'wp-js-hooks';
import { PremiumModal } from '../../../common/premium-modal';
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
      {__('Creating custom automations is a premium feature.', 'mailpoet')}
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

type FromScratchButtonProps = {
  variant?: Button.Props['variant'];
};

export function FromScratchButton({
  variant = 'secondary',
}: FromScratchButtonProps): JSX.Element {
  const [showModal, setShowModal] = useState(false);
  const [error, errorHandler] = useState(null);
  const [isBusy, setIsBusy] = useState(false);
  const onClickScratchButton = useCallback(() => {
    fromScratchHook(() => {
      setIsBusy(false);
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
        variant={variant}
        isBusy={isBusy && variant !== 'link'}
        disabled={isBusy}
        onClick={() => {
          setIsBusy(true);
          onClickScratchButton();
        }}
      >
        {__('Create custom automation', 'mailpoet')}
      </Button>
      <FromScratchPremiumModal showModal={showModal} onClose={premiumClose} />
    </>
  );
}
