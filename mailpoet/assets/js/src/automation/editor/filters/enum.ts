import { __ } from '@wordpress/i18n';
import { FilterType } from '../store/types';

export const filter: FilterType = {
  key: 'enum',
  fieldType: 'enum',
  formatValue: ({ args }, field) => {
    if (args.value === undefined) {
      return undefined;
    }

    const options = (field.args.options ?? []) as {
      id: string;
      name: string;
    }[];

    const label = options.find(({ id }) => id === args.value)?.name;
    return label ?? __('Unknown value', 'mailpoet');
  },
  edit: undefined,
};
