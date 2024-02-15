import { FilterType } from '../store/types';

export const filter: FilterType = {
  key: 'integer',
  fieldType: 'integer',
  formatValue: ({ args }) => {
    if (args.value === undefined) {
      return undefined;
    }
    return Array.isArray(args.value)
      ? args.value.join(' and ')
      : args.value.toString();
  },
  edit: undefined,
};
