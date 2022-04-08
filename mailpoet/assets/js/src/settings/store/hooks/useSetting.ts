import { useCallback } from 'react';
import { Settings } from '../types';
import useSelector from './useSelector';
import { ValueAndSetter } from './types';
import { useAction } from './useActions';
/**
 * Takes the path of a setting (ie. key1, key2, key3,...) and returns an array with two items:
 * the first is the setting value and the second is a setter for that setting.
 * Here we are declaring the signatures in case it takes 1, 2, and 3 keys.
 * Additional overloaded signatures can be added to go as deep as we want.
 * See:
 *  keyof: http://www.typescriptlang.org/docs/handbook/release-notes/typescript-2-1.html#keyof-and-lookup-types
 *  overloading functions: http://www.typescriptlang.org/docs/handbook/declaration-files/do-s-and-don-ts.html#function-overloads
 */
export function useSetting<Key1 extends keyof Settings>(
  key1: Key1,
): ValueAndSetter<Settings[Key1]>;
export function useSetting<
  Key1 extends keyof Settings,
  Key2 extends keyof Settings[Key1],
>(key1: Key1, key2: Key2): ValueAndSetter<Settings[Key1][Key2]>;
export function useSetting<
  Key1 extends keyof Settings,
  Key2 extends keyof Settings[Key1],
  Key3 extends keyof Settings[Key1][Key2],
>(
  key1: Key1,
  key2: Key2,
  key3: Key3,
): ValueAndSetter<Settings[Key1][Key2][Key3]>;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function useSetting(...path: string[]): [any, (value: any) => any] {
  const getValue = useSelector('getSetting');
  const setValue = useAction('setSetting');
  return [
    getValue(path),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    useCallback((value) => setValue(path, value), path),
  ];
}
