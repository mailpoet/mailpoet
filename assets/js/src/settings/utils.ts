import MailPoet from 'mailpoet';
import { ChangeEvent } from 'react';

type Setter = (value: string) => any
type Event = ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
export const onChange = (setter: Setter) => (e: Event) => setter(e.target.value);

export const t = ([word]: TemplateStringsArray) => MailPoet.I18n.t(word);
