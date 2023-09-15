import { __ } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../../../../editor/store';
import { OrderStatusPanel } from './order_status_panel';

export function Edit(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  return (
    <OrderStatusPanel
      label={__('Trigger settings', 'mailpoet')}
      showFrom
      showTo
      toLabel={__('Status changes to:', 'mailpoet')}
      fromLabel={__('Status changes from:', 'mailpoet')}
      onChange={(status, property) => {
        dispatch(storeName).updateStepArgs(selectedStep.id, property, status);
      }}
    />
  );
}
