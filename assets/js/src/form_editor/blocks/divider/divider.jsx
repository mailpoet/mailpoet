import MailPoet from 'mailpoet';
import icon from './icon.jsx';
import edit from './edit.jsx';

export const name = 'mailpoet-form/divider';

export const settings = {
  title: MailPoet.I18n.t('blockDivider'),
  description: null,
  icon,
  category: 'layout',
  attributes: {},
  supports: {
    html: false,
    multiple: true,
  },
  edit,
  save() {
    return null;
  },
};
