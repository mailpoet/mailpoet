import MailPoet from 'mailpoet';
import edit from './edit';
import icon from './icon.jsx';

export const name = 'mailpoet-form/submit-button';

export const settings = {
  title: MailPoet.I18n.t('blockSubmit'),
  description: MailPoet.I18n.t('blockSubmitDescription'),
  icon,
  category: 'obligatory',
  attributes: {
    label: {
      type: 'string',
      default: MailPoet.I18n.t('blockSubmitLabel'),
    },
  },
  supports: {
    html: false,
    inserter: false,
    multiple: false,
  },
  edit,
  save() {
    return null;
  },
};
