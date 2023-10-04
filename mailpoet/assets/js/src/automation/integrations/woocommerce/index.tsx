import { registerStepType } from '../../editor/store';
import { step as OrderStatusChanged } from './steps/order-status-changed';
import { step as AbandonedCartTrigger } from './steps/abandoned-cart';
import { MailPoet } from '../../../mailpoet';
import { step as BuysAProductTrigger } from './steps/buys-a-product';
import { step as BuysFromACategory } from './steps/buys-from-a-category';
import { step as MadeAReview } from './steps/made-a-review';
// Insert new imports here

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceActive) {
    return;
  }
  registerStepType(OrderStatusChanged);
  registerStepType(AbandonedCartTrigger);
  registerStepType(BuysAProductTrigger);
  registerStepType(BuysFromACategory);
  registerStepType(MadeAReview);
  // Insert new steps here
};
