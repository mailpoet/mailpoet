import { registerStepType } from '../../editor/store';
import { step as OrderStatusChanged } from './steps/order_status_changed';
import { step as AbandonedCartTrigger } from './steps/abandoned_cart';
import { MailPoet } from '../../../mailpoet';
// Insert new imports here

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceActive) {
    return;
  }
  registerStepType(OrderStatusChanged);
  registerStepType(AbandonedCartTrigger);
  // Insert new steps here
};
