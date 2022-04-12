import { MailPoet } from 'mailpoet';

export const t = (word: string): string => MailPoet.I18n.t(word);
