import { MailPoet } from '../../../mailpoet';
import { step as SubscriptionStatusChanged } from './steps/subscription-status-changed';
import { step as SubscriptionCreated } from './steps/subscription-created';
import { step as SubscriptionTrialEnded } from './steps/subscription-trial-ended';
import { step as SubscriptionTrialStarted } from './steps/subscription-trial-started';
import { step as SubscriptionRenewed } from './steps/subscription-renewed';
import { step as SubscriptionPaymentFailed } from './steps/subscription-payment-failed';
import { step as SubscriptionExpired } from './steps/subscription-expired';
import { registerStepType } from '../../editor/store';

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
  // Insert new steps here
};
