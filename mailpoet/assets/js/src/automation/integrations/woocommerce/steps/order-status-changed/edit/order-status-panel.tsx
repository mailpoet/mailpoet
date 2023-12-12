import { useState } from 'react';
import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { PlainBodyTitle } from '../../../../../editor/components';
import { PanelBody } from '../../../../../editor/components/panel/panel-body';
import { storeName } from '../../../../../editor/store';
import { PremiumModal } from '../../../../../../common/premium-modal';
import { getOrderStatusOptions, COMPLETED_ORDER_STATUS } from './order-status';
import { OrderStatusOptions } from '../../../../../types/filters';

type OrderStatusPanelProps = {
  label: string;
  showFrom: boolean;
  showTo: boolean;
  toLabel?: string;
  fromLabel?: string;
  onChange: (value: string, type: 'from' | 'to') => void;
};
export function OrderStatusPanel({
  label,
  showFrom,
  showTo,
  toLabel,
  fromLabel,
  onChange,
}: OrderStatusPanelProps): JSX.Element {
  const [showPremiumModal, setShowPremiumModal] = useState(false);
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );
  const options: OrderStatusOptions = new Map();
  options.set('any', {
    value: 'any',
    label: __('Any', 'mailpoet'),
    isDisabled: false,
  });
  getOrderStatusOptions().forEach((option) =>
    options.set(option.value, option),
  );

  const fromSelected = (selectedStep.args?.from as string) ?? 'any';
  const toSelected =
    (selectedStep.args?.to as string) ?? COMPLETED_ORDER_STATUS;

  const update = (value: string, property: 'from' | 'to') => {
    const status = options.get(value).isDisabled
      ? COMPLETED_ORDER_STATUS
      : value;
    onChange(status, property);
    setShowPremiumModal(options.get(value).isDisabled);
  };
  return (
    <PanelBody opened>
      <PlainBodyTitle title={label} />
      {showFrom && (
        <SelectControl
          value={fromSelected}
          label={fromLabel}
          options={[...options.values()]}
          onChange={(value) => update(value as string, 'from')}
        />
      )}
      {showTo && (
        <SelectControl
          value={toSelected}
          label={toLabel}
          options={[...options.values()]}
          onChange={(value) => update(value as string, 'to')}
        />
      )}
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
