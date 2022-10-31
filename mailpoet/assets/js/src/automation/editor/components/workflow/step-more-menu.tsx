import { useState } from 'react';
import { Dropdown, DropdownMenu, MenuItem } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { moreVertical, trash } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { PremiumModal } from 'common/premium_modal';
import { Step as StepData } from './types';
import { storeName } from '../../store';

type Props = {
  step: StepData;
};

// I do not like this hack.
// What I want to achieve: With the lazyOnClose method, I want to be able to control to close
// the menu from within a menuitem.
// But there must be a better way then storing currentProps like that.
let currentProps: Dropdown.RenderProps | null = null;
function setCurrentProps(current) {
  currentProps = current;
}

export function StepMoreMenu({ step }: Props): JSX.Element {
  const { stepType } = useSelect(
    (select) => ({
      stepType: select(storeName).getStepType(step.key),
    }),
    [step],
  );
  const [showModal, setShowModal] = useState(false);

  const lazyOnClose = () => {
    currentProps?.onClose();
  };
  type MoreControls = Record<string, JSX.Element>;
  const controls: MoreControls = Hooks.applyFilters(
    'mailpoet.automation.workflow.step.more-controls',
    {
      delete: (
        <MenuItem key="delete" icon={trash} onClick={() => setShowModal(true)}>
          {__('Delete step', 'mailpoet')}
        </MenuItem>
      ),
    },
    step,
    stepType,
    lazyOnClose,
  );
  return (
    <>
      <div className="mailpoet-automation-step-more-menu">
        <DropdownMenu
          label={__('More', 'mailpoet')}
          icon={moreVertical}
          popoverProps={{ position: 'bottom right' }}
          toggleProps={{ isSmall: true }}
        >
          {(props) => {
            setCurrentProps(props);
            return <>{Object.values(controls)}</>;
          }}
        </DropdownMenu>
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
          {__('You cannot remove a step from the automation.', 'mailpoet')}
        </PremiumModal>
      )}
    </>
  );
}
