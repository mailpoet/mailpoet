import { dispatch } from '@wordpress/data';
import { Hooks } from 'wp-js-hooks';
import { storeName } from './constants';
import { FilterType } from './types';

export const registerFilterType = (filterType: FilterType): void => {
  void dispatch(storeName).registerFilterType(
    Hooks.applyFilters('mailpoet.automation.register_filter_type', filterType),
  );
};
