import { dispatch } from '@wordpress/data';
import { store } from './store';
import { StepType } from './types';

export const registerStepType = (stepType: StepType): void => {
  dispatch(store).registerStepType(stepType);
};
