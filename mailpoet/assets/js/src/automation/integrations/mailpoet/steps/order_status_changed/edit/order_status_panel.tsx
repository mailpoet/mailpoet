import { useState } from 'react';
import { SelectControl } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { PlainBodyTitle } from '../../../../../editor/components';
import { PanelBody } from '../../../../../editor/components/panel/panel-body';
import { storeName } from '../../../../../editor/store';
import { PremiumModal } from '../../../../../../common/premium_modal';
import { getOrderStatusOptions, COMPLETED_ORDER_STATUS } from './order_status';
import { OrderStatusOptions } from '../../../../../types/filters';

export function OrderStatusPanel(): JSX.Element {
  const [showPremiumModal, setShowPremiumModal] = useState(false);
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );
  const options: OrderStatusOptions = {
    any: {
      value: 'any',
      label: __('Any', 'mailpoet'),
      isDisabled: false,
    },
    ...getOrderStatusOptions(),
  };

  const fromSelected = (selectedStep.args?.from as string) ?? 'any';
  const toSelected =
    (selectedStep.args?.to as string) ?? COMPLETED_ORDER_STATUS;

  const update = (value: string, property: string) => {
    const status = options[value].isDisabled ? COMPLETED_ORDER_STATUS : value;
    dispatch(storeName).updateStepArgs(selectedStep.id, property, status);
    setShowPremiumModal(options[value].isDisabled);
  };
  return (
    <PanelBody opened>
      <PlainBodyTitle title={__('Trigger settings', 'mailpoet')} />
      <SelectControl
        value={fromSelected}
        label={__('Status changes from:', 'mailpoet')}
        options={Object.values(options)}
        onChange={(value) => update(value, 'from')}
      />
      <SelectControl
        value={toSelected}
        label={__('Status changes to:', 'mailpoet')}
        options={Object.values(options)}
        onChange={(value) => update(value, 'to')}
      />
      {showPremiumModal && (
        <PremiumModal
          onRequestClose={() => setShowPremiumModal(false)}
          tracking={{
            utm_medium: 'upsell_modal',
            utm_campaign: 'trigger_order_status_changed',
          }}
        >
          {__('Changing the status is a premium feature.', 'mailpoet')}
        </PremiumModal>
      )}
    </PanelBody>
  );
}
