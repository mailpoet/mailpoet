import { select, dispatch } from '@wordpress/data';
import { DynamicSegmentQuery } from '../types';
import { storeName } from '../store';

const defaultQuery = {
  offset: 0,
  limit: 2,
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
  const queryKeys = Object.keys(query);

  const integerKeys = ['limit', 'offset'];
  for (
    let pathElementsIndex = 0;
    pathElementsIndex < pathElements.length;
    pathElementsIndex += 1
  ) {
    for (
      let queryKeysIndex = 0;
      queryKeysIndex < queryKeys.length;
      queryKeysIndex += 1
    ) {
      const currentKey = queryKeys[queryKeysIndex];
      if (pathElements[pathElementsIndex].startsWith(`${currentKey}[`)) {
        const currentValue = pathElements[pathElementsIndex]
          .replace(`${currentKey}[`, '')
          .replace(']', '');
        query[currentKey] = integerKeys.includes(currentKey)
          ? parseInt(currentValue, 10)
          : currentValue;
      }
    }
  }

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
