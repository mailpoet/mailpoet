import { MailPoet } from 'mailpoet';

export const isEmail = (value: string): boolean =>
  MailPoet.emailRegex.test(value);
