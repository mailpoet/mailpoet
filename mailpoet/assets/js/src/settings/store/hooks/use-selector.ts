import { useSelect } from '@wordpress/data';
import * as selectors from '../selectors';
import { ExcludeFirstParam } from './types';
import { STORE_NAME } from '../store-name';

type Selectors = typeof selectors;

export function useSelector<Key extends keyof Selectors>(
  key: Key,
): ExcludeFirstParam<Selectors[Key]> {
  const selector = useSelect((select) => select(STORE_NAME)[key], [key]);

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  return ((...args: any[]) => (selector as any)(...args)) as ExcludeFirstParam<
    Selectors[Key]
  >;
}
