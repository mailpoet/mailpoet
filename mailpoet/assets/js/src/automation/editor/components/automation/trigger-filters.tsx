import { __ } from '@wordpress/i18n';
import { FilterStrings } from '../filters';

export const triggerFilterStrings: FilterStrings = {
  title: __('Trigger filters', 'mailpoet'),
  label: __('Filters', 'mailpoet'),
  addFilter: __('Add trigger filter', 'mailpoet'),
  groupDescription: __(
    'The automation would only be started if the following trigger conditions are met:',
    'mailpoet',
  ),
  andDescription: __(
    'The automation would only be started if <operator>all of</operator> the following trigger conditions are met:',
    'mailpoet',
  ),
  orDescription: __(
    'The automation would only be started if <operator>any of</operator> the following trigger conditions are met:',
    'mailpoet',
  ),
  premiumMessage: __(
    'Adding trigger filters is a premium feature.',
    'mailpoet',
  ),
};
