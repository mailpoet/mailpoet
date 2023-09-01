import { registerStepType } from '../../editor/store';
import { step as DelayStep } from './steps/delay';
// Insert new imports here

export const initialize = (): void => {
  registerStepType(DelayStep);
  // Insert new steps here
};
