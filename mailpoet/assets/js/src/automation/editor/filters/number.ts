import { validateInTheLastParam } from './params/in-the-last';
import { FilterType } from '../store/types';

export const filter: FilterType = {
  key: 'number',
  fieldType: 'number',
  formatValue: ({ args }) => {
    if (args.value === undefined) {
      return undefined;
    }
    return Array.isArray(args.value)
      ? args.value.join(' and ')
      : args.value.toString();
  },
  validateArgs: (args, condition) => {
    if (!validateInTheLastParam(args)) {
      return false;
    }

    const value = args.value;
    if (['between', 'not-between'].includes(condition)) {
      return (
        Array.isArray(value) &&
        value.length === 2 &&
        value.every((item) => typeof item === 'number')
      );
    }

    if (['is-set', 'is-not-set'].includes(condition)) {
      return value === undefined;
    }

    return typeof value === 'number';
  },
  edit: undefined,
};
