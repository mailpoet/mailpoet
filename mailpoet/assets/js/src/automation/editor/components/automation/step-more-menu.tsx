import { useContext, useState } from 'react';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical, trash } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { PremiumModal } from 'common/premium-modal';
import { AutomationContext } from './context';
import { Step as StepData } from './types';
import { StepMoreControlsType } from '../../../types/filters';

type Props = {
  step: StepData;
};

export function StepMoreMenu({ step }: Props): JSX.Element {
  const { context } = useContext(AutomationContext);
  const [showModal, setShowModal] = useState(false);

  const moreControls: StepMoreControlsType = Hooks.applyFilters(
    'mailpoet.automation.step.more-controls',
    {
      delete: {
        key: 'delete',
        control: {
          title: __('Delete step', 'mailpoet'),
          icon: trash,
          onClick: () => setShowModal(true),
        },
        slot: () => {
          if (!showModal) {
            return false;
          }
          return (
            <PremiumModal
              onRequestClose={() => {
                setShowModal(false);
              }}
              tracking={{
                utm_medium: 'upsell_modal',
                utm_campaign: 'remove_automation_step',
              }}
            >
              {__('You cannot remove a step from the automation.', 'mailpoet')}
            </PremiumModal>
          );
        },
      },
    },
    step,
    context,
  );

  const slots = Object.values(moreControls).filter(
    (item) => item.slot !== undefined,
  );
  const controls = Object.values(moreControls).map((item) => item.control);
  return (
    <div className="mailpoet-automation-step-more-menu">
      {slots.map(({ key, slot: Slot }) => (
        <Slot key={key} />
      ))}
      <DropdownMenu
        label={__('More', 'mailpoet')}
        icon={moreVertical}
        popoverProps={{ position: 'bottom right' }}
        toggleProps={{ isSmall: true }}
        controls={Object.values(controls)}
      />
    </div>
  );
}
