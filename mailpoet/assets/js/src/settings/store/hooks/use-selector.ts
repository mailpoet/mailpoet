import { useSelect } from '@wordpress/data';
import * as selectors from '../selectors';
import { ExcludeFirstParam } from './types';
import { STORE_NAME } from '../store-name';

type Selectors = typeof selectors;

export function useSelector<Key extends keyof Selectors>(
  key: Key,
  deps: any[] = [], // eslint-disable-line @typescript-eslint/no-explicit-any
): ExcludeFirstParam<Selectors[Key]> {
  return useSelect((select) => {
    const selects = select(STORE_NAME);
    return selects[key].bind(selects);
  }, deps);
}
