import { dispatch } from '@wordpress/data';
import { Hooks } from 'wp-js-hooks';
import { store } from './store';
import { StepType } from './types';

export const registerStepType = (stepType: StepType): void => {
  dispatch(store).registerStepType(
    Hooks.applyFilters('mailpoet.automation.register_step_type', stepType),
  );
};
