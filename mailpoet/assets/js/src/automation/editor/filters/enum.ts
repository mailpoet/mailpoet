import { __ } from '@wordpress/i18n';
import {
  formatInTheLastParam,
  validateInTheLastParam,
} from './params/in-the-last';
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
  formatParams: ({ args }) => formatInTheLastParam(args),
  validateArgs: (args, _, field) => {
    if (!validateInTheLastParam(args)) {
      return false;
    }

    const value = args.value;
    const options = (field.args.options ?? []) as {
      id: string;
      name: string;
    }[];
    return (
      Array.isArray(value) &&
      value.every((item) => options.some(({ id }) => id === item))
    );
  },
  edit: undefined,
};
