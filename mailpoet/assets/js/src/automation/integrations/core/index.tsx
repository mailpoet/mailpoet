import { registerStepType } from '../../editor/store';
import { step as DelayStep } from './steps/delay';
import { step as IfElseStep } from './steps/if-else';
// Insert new imports here

export const initialize = (): void => {
  registerStepType(DelayStep);
  registerStepType(IfElseStep);
  // Insert new steps here
};
