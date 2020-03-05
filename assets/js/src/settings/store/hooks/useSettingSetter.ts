import { Settings, Action } from '../types';
import { useAction } from './useActions';

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
