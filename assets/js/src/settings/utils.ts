import MailPoet from 'mailpoet';
import { ChangeEvent } from 'react';

type Setter = (value: string) => any
type Event = ChangeEvent<any>

export const onChange = (setter: Setter) => (e: Event) => setter(e.target.value);

export const onToggle = (setter: Setter) => (e: Event) => setter(e.target.checked ? '1' : '0');

export const t = ([word]: TemplateStringsArray) => MailPoet.I18n.t(word);

export const isEmail = (value: string): boolean => (window as any).mailpoet_email_regex.test(value);
