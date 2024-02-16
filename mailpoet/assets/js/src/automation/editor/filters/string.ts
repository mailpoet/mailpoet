import { FilterType } from '../store/types';

export const filter: FilterType = {
  key: 'string',
  fieldType: 'string',
  formatValue: ({ args }) => {
    if (args.value === undefined) {
      return undefined;
    }
    return args.value.toString();
  },
  validateArgs: (args, condition) => {
    const value = args.value;
    if (['is-blank', 'is-not-blank'].includes(condition)) {
      return value === undefined;
    }
    return typeof value === 'string';
  },
  edit: undefined,
};
