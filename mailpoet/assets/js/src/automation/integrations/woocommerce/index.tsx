import { registerStepType } from '../../editor/store';
import { step as OrderStatusChanged } from './steps/order_status_changed';
import { step as AbandonedCartTrigger } from './steps/abandoned_cart';
import { MailPoet } from '../../../mailpoet';
import { step as BuysAProductTrigger } from './steps/buys_a_product';
import { step as BuysFromACategory } from './steps/buys_from_a_category';
// Insert new imports here

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceActive) {
    return;
  }
  registerStepType(OrderStatusChanged);
  registerStepType(AbandonedCartTrigger);
  registerStepType(BuysAProductTrigger);
  registerStepType(BuysFromACategory);
  // Insert new steps here
};
