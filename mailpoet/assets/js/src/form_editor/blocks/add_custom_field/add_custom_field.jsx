import { MailPoet } from 'mailpoet';
import { Icon } from './icon.jsx';
import { AddCustomField } from './edit.jsx';

export const name = 'mailpoet-form/add-custom-field';

export const settings = {
  title: MailPoet.I18n.t('blockAddCustomField'),
  description: MailPoet.I18n.t('blockAddCustomFieldDescription'),
  icon: Icon,
  category: 'custom-fields',
  attributes: {},
  supports: {
    html: false,
    multiple: false,
  },
  edit: AddCustomField,
  save() {
    return null;
  },
};
