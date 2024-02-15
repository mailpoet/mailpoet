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
  edit: undefined,
};
