import { useCallback, useState } from 'react';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical, trash } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { PremiumModal } from 'common/premium_modal';
import { Step as StepData } from './types';

type Props = {
  step: StepData;
};

export function StepMoreMenu({ step }: Props): JSX.Element {
  const [showModal, setShowModal] = useState(false);

  const onDelete = useCallback((stepData: StepData) => {
    const deleteStepCallback = Hooks.applyFilters(
      'mailpoet.automation.workflow.delete_step_callback',
      () => {
        setShowModal(true);
      },
    );
    deleteStepCallback(stepData);
  }, []);

  return (
    <>
      <div className="mailpoet-automation-step-more-menu">
        <DropdownMenu
          label={__('More', 'mailpoet')}
          icon={moreVertical}
          controls={[
            {
              title: __('Delete step', 'mailpoet'),
              icon: trash,
              onClick: () => onDelete(step),
            },
          ]}
          popoverProps={{ position: 'bottom right' }}
          toggleProps={{ isSmall: true }}
        />
      </div>

      {showModal && (
        <PremiumModal
          onRequestClose={() => {
            setShowModal(false);
          }}
          tracking={{
            utm_medium: 'upsell_modal',
            utm_campaign: 'remove_automation_step',
          }}
        >
          {__('You cannot remove a new step from the automation.', 'mailpoet')}
        </PremiumModal>
      )}
    </>
  );
}
