import { MailPoet } from '../../../mailpoet';
import { step as BookingCreated } from './steps/booking-created';
import { step as BookingStarts } from './steps/booking-starts';
import { step as BookingStatusChanged } from './steps/bookings-status-changed';
import { registerStepType } from '../../editor/store';

export const initialize = (): void => {
  if (!MailPoet.isWoocommerceBookingsActive) {
    return;
  }
  registerStepType(BookingCreated);
  registerStepType(BookingStarts);
  registerStepType(BookingStatusChanged);
};
