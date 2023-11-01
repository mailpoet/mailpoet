import { MailPoet } from '../../../mailpoet';
import { step as SubscriptionStatusChanged } from './steps/subscription-status-changed';
import { registerStepType } from '../../editor/store';

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceSubscriptionsActive) {
    return;
  }
  registerStepType(SubscriptionStatusChanged);
  // Insert new steps here
};
