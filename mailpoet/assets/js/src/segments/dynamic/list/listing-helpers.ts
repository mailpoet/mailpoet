import { select, dispatch } from '@wordpress/data';
import { DynamicSegmentQuery } from '../types';
import { storeName } from '../store';

const defaultQuery = {
  offset: 0,
  limit: 25,
  search: '',
  sort_by: 'updated_at',
  sort_order: 'desc',
  group: 'all',
};
export function updateDynamicQuery(values: Partial<DynamicSegmentQuery>): void {
  const currentQuery = select(storeName).getDynamicSegmentsQuery();
  const query = currentQuery ?? defaultQuery;
  const newQuery = { ...query, ...values };
  if (
    currentQuery !== null &&
    JSON.stringify(query) === JSON.stringify(newQuery)
  ) {
    return;
  }
  dispatch(storeName).updateDynamicSegmentsQuery(newQuery);
}

export function updateDynamicQueryFromLocation(pathname: string): void {
  const pathElements = pathname.split('/');
  const currentQuery = select(storeName).getDynamicSegmentsQuery();
  const query = currentQuery !== null ? currentQuery : defaultQuery;

  const integerKeys = ['limit', 'offset'];

  pathElements.forEach((element) => {
    const match = element.match(/(\w+)\[(.*?)]/);
    if (match) {
      const [, key, value] = match;
      query[key] = integerKeys.includes(key) ? parseInt(value, 10) : value;
    }
  });

  updateDynamicQuery(query);
}

export function getTabFromLocation(pathname: string): string {
  const pathElements = pathname.split('/');
  for (let i = 0; i < pathElements.length; i += 1) {
    if (pathElements[i].startsWith(`group[`)) {
      return pathElements[i].replace(`group[`, '').replace(']', '');
    }
  }
  return 'all';
}
