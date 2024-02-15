import { filter as booleanFilter } from './boolean';
import { filter as numberFilter } from './number';
import { filter as integerFilter } from './integer';
import { filter as stringFilter } from './string';
import { filter as datetimeFilter } from './datetime';
import { filter as enumFilter } from './enum';
import { filter as enumArrayFilter } from './enum-array';
import { registerFilterType } from '../store/register-filter-type';

export const initializeFilters = (): void => {
  registerFilterType(booleanFilter);
  registerFilterType(numberFilter);
  registerFilterType(integerFilter);
  registerFilterType(stringFilter);
  registerFilterType(datetimeFilter);
  registerFilterType(enumFilter);
  registerFilterType(enumArrayFilter);
};
