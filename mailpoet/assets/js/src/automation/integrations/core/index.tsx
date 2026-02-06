import { registerStepType } from '../../editor/store';
import { step as DelayStep } from './steps/delay';
import { step as IfElseStep } from './steps/if-else';
import { step as ScheduledDateTimeStep } from './steps/scheduled-date-time';
// Insert new imports here

export const initialize = (): void => {
  registerStepType(DelayStep);
  registerStepType(IfElseStep);
  registerStepType(ScheduledDateTimeStep);
  // Insert new steps here
};
