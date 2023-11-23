import { select, dispatch } from '@wordpress/data';
import { DynamicSegmentQuery } from '../types';
import { storeName } from '../store';

export function updateDynamicQuery(values: Partial<DynamicSegmentQuery>): void {
  const defaultQuery = {
    offset: 0,
    limit: 2,
    filter: {},
    search: '',
    sort_by: 'updated_at',
    sort_order: 'desc',
    group: 'all',
  };
  const currentQuery = select(storeName).getDynamicSegmentsQuery();
  const query = currentQuery ?? defaultQuery;
  const newQuery = { ...query, ...values };
  if (JSON.stringify(query) === JSON.stringify(newQuery)) {
    return;
  }
  dispatch(storeName).updateDynamicSegmentsQuery(newQuery);
}

export function updateDynamicQueryFromLocation(pathname: string): void {
  const pathElements = pathname.split('/');
  if (pathElements[1] !== 'segments') {
    return;
  }
  const defaultQuery = {
    offset: 0,
    limit: 2,
    filter: {},
    search: '',
    sort_by: 'updated_at',
    sort_order: 'desc',
    group: 'all',
  };
  const currentQuery = select(storeName).getDynamicSegmentsQuery();
  const query = currentQuery !== null ? currentQuery : defaultQuery;
  const queryKeys = Object.keys(query);

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
      if (
        pathElements[pathElementsIndex].startsWith(
          `${queryKeys[queryKeysIndex]}[`,
        )
      ) {
        query[queryKeys[queryKeysIndex]] = pathElements[pathElementsIndex]
          .replace(`${queryKeys[queryKeysIndex]}[`, '')
          .replace(']', '');
      }
    }
  }
  updateDynamicQuery(query);
}
