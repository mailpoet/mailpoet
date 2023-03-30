import { registerStepType } from '../../editor/store';
import { step as OrderStatusChanged } from './steps/order_status_changed';
import { MailPoet } from '../../../mailpoet';

export const initialize = (): void => {
  // @ToDo Register once transactional emails are implemented
  return;

  if (!MailPoet.isWoocommerceActive) {
    return;
  }
  registerStepType(OrderStatusChanged);
};
