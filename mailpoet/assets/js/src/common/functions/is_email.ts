import MailPoet from 'mailpoet';

export default (value: string): boolean => MailPoet.emailRegex.test(value);
