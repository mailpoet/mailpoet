import { ChangeEvent } from 'react';

type Setter = (value: string) => any
type Event = ChangeEvent<any>

export function onChange(setter: Setter) {
  return (e: Event) => setter(e.target.value);
}

export function onToggle(setter: Setter, falseValue = '0') {
  return (e: Event) => setter(e.target.checked ? '1' : falseValue);
}
