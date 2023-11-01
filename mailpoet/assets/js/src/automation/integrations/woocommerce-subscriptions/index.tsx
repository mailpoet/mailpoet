import { MailPoet } from '../../../mailpoet';
import { step as SubscriptionStatusChanged } from './steps/subscription-status-changed';
import { step as SubscriptionCreated } from './steps/subscription-created';
import { registerStepType } from '../../editor/store';

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceSubscriptionsActive) {
    return;
  }
  registerStepType(SubscriptionStatusChanged);
  registerStepType(SubscriptionCreated);
  // Insert new steps here
};
