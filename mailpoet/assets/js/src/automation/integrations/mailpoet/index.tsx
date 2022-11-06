import { registerStepType } from '../../editor/store';
import { step as SendEmailStep } from './steps/send_email';
import { step as SomeoneSubscribesTrigger } from './steps/someone-subscribes';
import { step as WpUserRegisteredTrigger } from './steps/wp-user-registered';
import { registerStepControls } from './step-controls';

export const initialize = (): void => {
  registerStepType(SendEmailStep);
  registerStepType(WpUserRegisteredTrigger);
  registerStepType(SomeoneSubscribesTrigger);
  registerStepControls();
};
