import { registerStepType } from '../../editor/store';
import { step as SendEmailStep } from './steps/send_email';
import { step as SomeoneSubscribedTrigger } from './steps/someone-subscribed';
import { step as WpUserRegisteredTrigger } from './steps/wp-user-registered';

export const initialize = (): void => {
  registerStepType(SendEmailStep);
  registerStepType(WpUserRegisteredTrigger);
  registerStepType(SomeoneSubscribedTrigger);
};
