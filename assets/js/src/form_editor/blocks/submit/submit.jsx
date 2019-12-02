import MailPoet from 'mailpoet';
import edit from './edit.jsx';
import icon from './icon.jsx';

export const name = 'mailpoet-form/submit-button';

export const settings = {
  title: MailPoet.I18n.t('blockSubmit'),
  description: MailPoet.I18n.t('blockSubmitDescription'),
  icon,
  attributes: {
    label: {
      type: 'string',
      default: MailPoet.I18n.t('blockSubmitLabel'),
    },
  },
  category: 'obligatory',
  supports: {
    html: false,
    customClassName: false,
    inserter: false,
  },
  edit,
  save() {
    return null;
  },
};
