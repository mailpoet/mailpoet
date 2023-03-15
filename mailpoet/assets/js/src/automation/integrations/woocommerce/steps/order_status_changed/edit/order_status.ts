import { OrderStatusOptions } from '../../../../../types/filters';
import { Hooks } from '../../../../../../hooks';
import { getContext } from '../../../context';

export const COMPLETED_ORDER_STATUS = 'wc-completed';
export function getOrderStatusOptions(): OrderStatusOptions {
  const options = new Map();
  const context = getContext();
  Object.keys(context?.order_statuses || {}).forEach((value: string) => {
    options.set(value, {
      value,
      label: context.order_statuses[value],
      isDisabled: value !== COMPLETED_ORDER_STATUS,
    });
  });
  return Hooks.applyFilters(
    'mailpoet.automation.trigger.order_status_changed.order_status_options',
    options,
  );
}
