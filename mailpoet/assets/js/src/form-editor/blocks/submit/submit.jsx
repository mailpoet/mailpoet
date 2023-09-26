import { MailPoet } from 'mailpoet';
import { SubmitEdit } from './edit';
import { Icon } from './icon.jsx';

export const name = 'mailpoet-form/submit-button';

export const settings = {
  title: MailPoet.I18n.t('blockSubmit'),
  description: MailPoet.I18n.t('blockSubmitDescription'),
  icon: Icon,
  category: 'obligatory',
  attributes: {
    label: {
      type: 'string',
      default: MailPoet.I18n.t('blockSubmitLabel'),
    },
    styles: {
      type: 'object',
      default: {
        fullWidth: true,
        inheritFromTheme: true,
      },
    },
  },
  supports: {
    html: false,
    inserter: false,
    multiple: false,
  },
  edit: SubmitEdit,
  save() {
    return null;
  },
};
