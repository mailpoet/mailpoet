import { __ } from '@wordpress/i18n';
import { FilterStrings } from '../../../../editor/components/filters';

export const strings: FilterStrings = {
  title: __('Conditions', 'mailpoet'),
  // translators: %d is the number of conditions that are set up
  label: __('Conditions: %d', 'mailpoet'),
  addFilter: __('Add condition', 'mailpoet'),
  groupDescription: __(
    'The automation would go through the yes flow if the following conditions are met:',
    'mailpoet',
  ),
  andDescription: __(
    'The automation would go through the yes flow if <operator>all of</operator> the following conditions are met:',
    'mailpoet',
  ),
  orDescription: __(
    'The automation would go through the yes flow if <operator>any of</operator> the following conditions are met:',
    'mailpoet',
  ),
  premiumMessage: __(
    'Adding trigger filters is a premium feature.',
    'mailpoet',
  ),
};
