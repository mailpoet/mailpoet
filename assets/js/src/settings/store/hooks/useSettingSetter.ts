import { Settings, Action } from '../types';
import { useAction } from './useActions';

/**
 * Takes the path of a setting (ie. key1, key2, key3,...) and returns its setter.
 * Here we are declaring the signatures in case it takes 1, 2, and 3 keys.
 * Additional overloaded signatures can be added to go as deep as we want.
 * See:
 *  keyof: http://www.typescriptlang.org/docs/handbook/release-notes/typescript-2-1.html#keyof-and-lookup-types
 *  overloading functions: http://www.typescriptlang.org/docs/handbook/declaration-files/do-s-and-don-ts.html#function-overloads
 */
export function useSettingSetter<Key1 extends keyof Settings, Value extends Settings[Key1]>
  (key1: Key1): ((value: Value) => Promise<Action>);
export function useSettingSetter<
  Key1 extends keyof Settings,
  Key2 extends keyof Settings[Key1],
  Value extends Settings[Key1][Key2]>
  (key1: Key1, key2: Key2): ((value: Value) => Promise<Action>);
export function useSettingSetter<
  Key1 extends keyof Settings,
  Key2 extends keyof Settings[Key1],
  Key3 extends keyof Settings[Key1][Key2],
  Value extends Settings[Key1][Key2][Key3]>
  (key1: Key1, key2: Key2, key3: Key3): ((value: Value) => Promise<Action>);

export function useSettingSetter(...path: string[]) {
  const setValue = useAction('setSetting');
  return async (value) => setValue(path, value);
}
