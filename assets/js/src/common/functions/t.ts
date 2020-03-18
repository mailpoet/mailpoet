import MailPoet from 'mailpoet';

export default ([word]: TemplateStringsArray) => MailPoet.I18n.t(word);
