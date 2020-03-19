import MailPoet from 'mailpoet';

export default (word: string): string => MailPoet.I18n.t(word);
