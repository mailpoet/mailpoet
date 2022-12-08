import { dispatch } from '@wordpress/data';
import { Hooks } from 'wp-js-hooks';
import { storeName } from './constants';
import { StepType } from './types';

export const registerStepType = (stepType: StepType): void => {
  dispatch(storeName).registerStepType(
    Hooks.applyFilters('mailpoet.register_step_type', stepType),
  );
};
