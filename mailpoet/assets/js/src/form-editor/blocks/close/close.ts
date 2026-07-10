import { MailPoet } from 'mailpoet';
import { CloseEdit } from './edit';
import { Icon } from './icon';

export const name = 'mailpoet-form/close-button';

export const settings = {
  title: MailPoet.I18n.t('blockClose'),
  description: MailPoet.I18n.t('blockCloseDescription'),
  icon: Icon,
  category: 'design',
  attributes: {
    label: {
      type: 'string',
      default: MailPoet.I18n.t('blockCloseLabel'),
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
    inserter: true,
    multiple: false,
  },
  edit: CloseEdit,
  save() {
    return null;
  },
};
