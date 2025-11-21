import { useSelect } from '@wordpress/data';
import * as selectors from '../selectors';
import { SelectorResult } from './types';
import { STORE_NAME } from '../store-name';

type Selectors = typeof selectors;

export function useSelector<Key extends keyof Selectors>(
  key: Key,
  deps: unknown[] = [],
): SelectorResult<Selectors[Key]> {
  return useSelect(
    (select) => {
      const storeSelects = select(STORE_NAME) as Record<
        string,
        (...args: unknown[]) => unknown
      >;
      const selectorFn = selectors[key];

      if (selectorFn.length <= 1) {
        return storeSelects[key as string]();
      }

      return storeSelects[key as string].bind(storeSelects);
    },
    [key, ...deps],
  );
}
