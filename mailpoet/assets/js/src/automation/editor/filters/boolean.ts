import { __ } from '@wordpress/i18n';
import { FilterType } from '../store/types';

export const filter: FilterType = {
  key: 'boolean',
  fieldType: 'boolean',
  formatValue: ({ args }) => {
    if (args.value === undefined) {
      return undefined;
    }
    return args.value ? __('Yes', 'mailpoet') : __('No', 'mailpoet');
  },
  validateArgs: (args) => typeof args.value === 'boolean',
  edit: undefined,
};
