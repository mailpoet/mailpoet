import { MailPoet } from '../../../mailpoet';
import { step as BookingCreated } from './steps/booking-created';
import { step as BookingStatusChanged } from './steps/bookings-status-changed';
import { registerStepType } from '../../editor/store';

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceBookingsActive) {
    return;
  }
  registerStepType(BookingCreated);
  registerStepType(BookingStatusChanged);
  // Insert new steps here
};
