import { __ } from '@wordpress/i18n';
import { FilterType } from '../store/types';

export const filter: FilterType = {
  key: 'enum_array',
  fieldType: 'enum_array',
  formatValue: ({ args }, field) => {
    if (args.value === undefined) {
      return undefined;
    }

    const options = (field.args.options ?? []) as {
      id: string;
      name: string;
    }[];
    const values = Array.isArray(args.value) ? args.value : [args.value];
    const labels = values
      .map((v) => options.find(({ id }) => id === v)?.name)
      .filter((v) => v !== undefined);

    if (labels.length === 0) {
      return __('Unknown value', 'mailpoet');
    }

    const suffix =
      labels.length < values.length ? __('and unknown values', 'mailpoet') : '';
    return `${labels.join(', ')}${suffix}`;
  },
  edit: undefined,
};
