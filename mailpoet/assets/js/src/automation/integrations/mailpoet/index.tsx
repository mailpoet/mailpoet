import { registerStepType } from '../../editor/store';
import { step as SendEmailStep } from './steps/send_email';

export const initialize = (): void => {
  registerStepType(SendEmailStep);
};
