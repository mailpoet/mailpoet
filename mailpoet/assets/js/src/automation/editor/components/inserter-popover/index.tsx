import { useCallback, useRef, useState } from 'react';
import { Popover } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { PremiumModal } from 'common/premium_modal';
import { Inserter } from '../inserter';
import { Item } from '../inserter/item';
import { storeName } from '../../store';

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
    const addStepCallback = Hooks.applyFilters(
      'mailpoet.automation.workflow.add_step_callback',
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
        ref={popoverRef}
        anchorRect={inserterPopover.anchor.getBoundingClientRect()}
        onClose={() => {
          if (!showModal) {
            setInserterPopover(undefined);
          }
        }}
      >
        <Inserter onInsert={onInsert} />
      </Popover>

      {showModal && (
        <PremiumModal
          onRequestClose={() => {
            setShowModal(false);
            popoverRef.current?.focus();
          }}
        >
          {__('You cannot add a new step to the automation.', 'mailpoet')}
        </PremiumModal>
      )}
    </>
  );
}
