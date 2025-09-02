import { MailPoet } from '../../../mailpoet';
import { step as BookingStatusChanged } from './steps/bookings-status-changed';
import { registerStepType } from '../../editor/store';

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceBookingsActive) {
    return;
  }
  registerStepType(BookingStatusChanged);
  // Insert new steps here
};
