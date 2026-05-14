import { registerStepType } from '../../editor/store';
import { step as OrderStatusChanged } from './steps/order-status-changed';
import { step as OrderCompletedTrigger } from './steps/order-completed';
import { step as OrderCancelledTrigger } from './steps/order-cancelled';
import { step as OrderCreatedTrigger } from './steps/order-created';
import { step as OrderPaid } from './steps/order-paid';
import { step as OrderNoteAddedTrigger } from './steps/order-note-added';
import { step as AbandonedCartTrigger } from './steps/abandoned-cart';
import { MailPoet } from '../../../mailpoet';
import { step as BuysAProductTrigger } from './steps/buys-a-product';
import { step as BuysFromACategory } from './steps/buys-from-a-category';
import { step as BuysFromATag } from './steps/buys-from-a-tag';
import { step as MadeAReview } from './steps/made-a-review';
import { step as CustomerWinBack } from './steps/customer-win-back';
import { step as ChangeOrderStatus } from './steps/change-order-status';
import { step as AddOrderNote } from './steps/add-order-note';
import { step as SavedCardExpires } from './steps/saved-card-expires';
// Insert new imports here

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceActive) {
    return;
  }
  registerStepType(OrderStatusChanged);
  registerStepType(OrderCompletedTrigger);
  registerStepType(OrderCancelledTrigger);
  registerStepType(OrderCreatedTrigger);
  registerStepType(OrderPaid);
  registerStepType(OrderNoteAddedTrigger);
  registerStepType(AbandonedCartTrigger);
  registerStepType(BuysAProductTrigger);
  registerStepType(BuysFromACategory);
  registerStepType(BuysFromATag);
  registerStepType(MadeAReview);
  registerStepType(CustomerWinBack);
  registerStepType(ChangeOrderStatus);
  registerStepType(AddOrderNote);
  registerStepType(SavedCardExpires);
  // Insert new steps here
};
