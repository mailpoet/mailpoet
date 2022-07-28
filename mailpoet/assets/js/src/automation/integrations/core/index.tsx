import { registerStepType } from '../../editor/store';
import { step as DelayStep } from './steps/delay';

export const initialize = (): void => {
  registerStepType(DelayStep);
};
