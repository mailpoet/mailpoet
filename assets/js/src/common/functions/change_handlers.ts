import { ChangeEvent } from 'react';

type Setter = (value: string) => any
type Event = ChangeEvent<any>

export const onChange = (setter: Setter) => (e: Event) => setter(e.target.value);

export const onToggle = (setter: Setter) => (e: Event) => setter(e.target.checked ? '1' : '0');
