import { MailPoet } from 'mailpoet';
import { Icon } from './icon.jsx';
import { CustomHtmlEdit } from './edit.jsx';

export const name = 'mailpoet-form/html';

export const settings = {
  title: MailPoet.I18n.t('blockCustomHtml'),
  description: MailPoet.I18n.t('blockCustomHtmlDescription'),
  icon: Icon,
  category: 'fields',
  attributes: {
    content: {
      type: 'string',
      default: MailPoet.I18n.t('blockCustomHtmlDefault'),
    },
    nl2br: {
      type: 'boolean',
      default: true,
    },
  },
  supports: {
    html: false,
    multiple: true,
  },
  edit: CustomHtmlEdit,
  save() {
    return null;
  },
};
