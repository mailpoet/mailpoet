import { useLocation } from 'react-router-dom';
import { useMemo } from 'react';

const defaultQuery = {
  offset: 0,
  limit: 25,
  search: '',
  sort_by: 'updated_at',
  sort_order: 'desc',
  group: 'all',
};

export type Query = typeof defaultQuery;

function parseQuery(path: string): Partial<Query> {
  return path
    .split('/')
    .map((part) => part.replace(/]$/, '').split('['))
    .map(([key, value]) => [
      key,
      typeof defaultQuery[key] === 'number' ? parseInt(value, 10) : value,
    ])
    .reduce((map, [k, v]) => ({ ...map, [k]: v }), {});
}

export function getSegmentsQuery(path: string = window.location.hash): Query {
  return { ...defaultQuery, ...parseQuery(path) };
}

export function useSegmentsQuery(): Query {
  const location = useLocation();
  return useMemo(() => getSegmentsQuery(location.pathname), [location]);
}

export function updateSegmentsQuery(query: Partial<Query>): void {
  const queryEntries = Object.entries({
    ...parseQuery(window.location.hash),
    ...query,
  })
    .filter(([key, value]) => value !== defaultQuery[key])
    .sort(([keyA], [keyB]) => keyA.localeCompare(keyB));

  const hash = queryEntries.reduce(
    (path, [key, value]) => (value ? `${path}/${key}[${value}]` : path),
    '',
  );
  window.location.hash = `/segments${hash}`;
}
