import { dispatch } from '@wordpress/data';
import { Hooks } from 'wp-js-hooks';
import { storeName } from './constants';
import { StepType } from './types';

export const registerStepType = (stepType: StepType): void => {
  void dispatch(storeName).registerStepType(
    Hooks.applyFilters('mailpoet.automation.register_step_type', stepType),
  );
};
