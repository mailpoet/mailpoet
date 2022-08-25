import { registerStepType } from '../../editor/store';
import { step as SendEmailStep } from './steps/send_email';
import { step as SomeoneSubscribedTrigger } from './steps/someone-subscribed';

export const initialize = (): void => {
  registerStepType(SendEmailStep);
  registerStepType(SomeoneSubscribedTrigger);
};
