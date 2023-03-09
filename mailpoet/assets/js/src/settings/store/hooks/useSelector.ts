import { useSelect } from '@wordpress/data';
import * as selectors from '../selectors';
import { ExcludeFirstParam } from './types';
import { store } from '../index';

type Selectors = typeof selectors;

export function useSelector<Key extends keyof Selectors>(
  key: Key,
  deps: any[] = [], // eslint-disable-line @typescript-eslint/no-explicit-any
): ExcludeFirstParam<Selectors[Key]> {
  return useSelect((select) => {
    const selects = select(store);
    return selects[key].bind(selects);
  }, deps);
}
