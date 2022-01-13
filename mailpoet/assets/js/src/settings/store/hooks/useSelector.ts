import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '..';
import * as selectors from '../selectors';
import { ExcludeFirstParam } from './types';

type Selectors = typeof selectors

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export default function useSelector<Key extends keyof Selectors>(key: Key, deps: any[] = []):
  ExcludeFirstParam<Selectors[Key]> {
  return useSelect((select) => {
    const selects = select(STORE_NAME);
    return selects[key].bind(selects);
  }, deps);
}
