import { dispatch } from '@wordpress/data';
import { storeName } from './constants';
import { StepType } from './types';

export const registerStepType = (stepType: StepType): void => {
  dispatch(storeName).registerStepType(stepType);
};
