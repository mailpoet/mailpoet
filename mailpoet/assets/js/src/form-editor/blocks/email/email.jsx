import { MailPoet } from 'mailpoet';
import { defaultBlockStyles } from 'form-editor/store/mapping/to-blocks/styles-mapper';
import { EmailEdit } from './edit.jsx';
import { Icon } from './icon.jsx';

export const name = 'mailpoet-form/email-input';

export const settings = {
  title: MailPoet.I18n.t('blockEmail'),
  description: MailPoet.I18n.t('blockEmailDescription'),
  icon: Icon,
  category: 'obligatory',
  attributes: {
    label: {
      type: 'string',
      default: MailPoet.I18n.t('blockEmail'),
    },
    labelWithinInput: {
      type: 'boolean',
      default: true,
    },
    styles: {
      type: 'object',
      default: defaultBlockStyles,
    },
  },
  supports: {
    html: false,
    inserter: false,
    multiple: false,
  },
  edit: EmailEdit,
  save() {
    return null;
  },
};
