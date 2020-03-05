import { Settings } from '../types';
import useSelector from './useSelector';

export function useSetting<Key1 extends keyof Settings>
  (key1: Key1): Settings[Key1];
export function useSetting<Key1 extends keyof Settings, Key2 extends keyof Settings[Key1]>
  (key1: Key1, key2: Key2): Settings[Key1][Key2];
export function useSetting<
  Key1 extends keyof Settings,
  Key2 extends keyof Settings[Key1],
  Key3 extends keyof Settings[Key1][Key2]>
  (key1: Key1, key2: Key2, key3: Key3): Settings[Key1][Key2][Key3];

export function useSetting(...path: string[]): any {
  const getValue = useSelector('getSetting');
  return getValue(path);
}
