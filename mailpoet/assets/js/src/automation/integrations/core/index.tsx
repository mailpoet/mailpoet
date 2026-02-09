import { registerStepType } from '../../editor/store';
import { step as DelayStep } from './steps/delay';
import { step as IfElseStep } from './steps/if-else';

export const initialize = (): void => {
  registerStepType(DelayStep);
  registerStepType(IfElseStep);
};
