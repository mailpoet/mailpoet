import { MailPoet } from '../../../mailpoet';
import { step as SubscriptionStatusChanged } from './steps/subscription-status-changed';
import { step as SubscriptionCreated } from './steps/subscription-created';
import { step as SubscriptionTrialEnded } from './steps/subscription-trial-ended';
import { step as SubscriptionTrialStarted } from './steps/subscription-trial-started';
import { step as SubscriptionRenewed } from './steps/subscription-renewed';
import { step as SubscriptionPaymentFailed } from './steps/subscription-payment-failed';
import { step as SubscriptionExpired } from './steps/subscription-expired';
import { registerStepType } from '../../editor/store';
import { step as ChangeSubscriptionStatus } from './steps/change-subscription-status';
import { step as AddProductToSubscription } from './steps/add-product-to-subscription';
import { step as RemoveProductFromSubscription } from './steps/remove-product-from-subscription';
import { step as UpdateProductOnSubscription } from './steps/update-product-on-subscription';

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceSubscriptionsActive) {
    return;
  }
  registerStepType(SubscriptionStatusChanged);
  registerStepType(SubscriptionCreated);
  registerStepType(SubscriptionTrialEnded);
  registerStepType(SubscriptionTrialStarted);
  registerStepType(SubscriptionRenewed);
  registerStepType(SubscriptionPaymentFailed);
  registerStepType(SubscriptionExpired);
  registerStepType(ChangeSubscriptionStatus);
  registerStepType(AddProductToSubscription);
  registerStepType(RemoveProductFromSubscription);
  registerStepType(UpdateProductOnSubscription);
  // Insert new steps here
};
