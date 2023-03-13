import { OrderStatusOptions } from '../../../../../types/filters';
import { Hooks } from '../../../../../../hooks';
import { getContext } from '../../../context';

export const COMPLETED_ORDER_STATUS = 'wc-completed';
export function getOrderStatusOptions(): OrderStatusOptions {
  return Hooks.applyFilters(
    'mailpoet.automation.trigger.order_status_changed.order_status_options',
    Object.keys(getContext().woocommerce?.order_statuses || {})
      .map((value: string) => ({
        value,
        label: getContext().woocommerce.order_statuses[value],
        isDisabled: value !== COMPLETED_ORDER_STATUS,
      }))
      .reduce((acc, curr) => {
        acc[curr.value] = curr;
        return acc;
      }, {}),
  );
}
