import { MailPoet } from '../../../mailpoet';

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceSubscriptionsActive) {
    return;
  }
  // Insert new steps here
};
