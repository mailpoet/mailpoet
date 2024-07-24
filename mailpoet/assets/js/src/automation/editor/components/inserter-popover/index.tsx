import { useCallback, useRef, useState } from 'react';
import { Popover } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { PremiumModal } from 'common/premium-modal';
import { Inserter } from '../inserter';
import { Item } from '../inserter/item';
import { storeName } from '../../store';
import { AddStepCallbackType } from '../../../types/filters';

export function InserterPopover(): JSX.Element | null {
  const popoverRef = useRef<HTMLDivElement>();
  const [showModal, setShowModal] = useState(false);
  const { inserterPopover } = useSelect(
    (select) => ({
      inserterPopover: select(storeName).getInserterPopover(),
    }),
    [],
  );
  const { setInserterPopover } = useDispatch(storeName);

  const onInsert = useCallback((item: Item) => {
    const addStepCallback: AddStepCallbackType = Hooks.applyFilters(
      'mailpoet.automation.add_step_callback',
      () => {
        setShowModal(true);
      },
    );
    addStepCallback(item);
  }, []);

  if (!inserterPopover) {
    return null;
  }

  return (
    <>
      <Popover
        placement="bottom"
        ref={popoverRef}
        anchor={inserterPopover.anchor}
        onClose={() => {
          if (!showModal) {
            void setInserterPopover(undefined);
          }
        }}
      >
        <Inserter onInsert={onInsert} showInserterHelpPanel={false} />
      </Popover>

      {showModal && (
        <PremiumModal
          onRequestClose={() => {
            setShowModal(false);
            popoverRef.current?.focus();
          }}
          tracking={{
            utm_medium: 'upsell_modal',
            utm_campaign: 'add_automation_step',
          }}
          data={{ capabilities: { automationSteps: 0 } }}
        >
          {__('You cannot add a new step to the automation.', 'mailpoet')}
        </PremiumModal>
      )}
    </>
  );
}
