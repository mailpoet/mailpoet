import MailPoet from 'mailpoet';
import { defaultBlockStyles } from 'form_editor/store/mapping/to_blocks/styles_mapper';
import edit from './edit.jsx';
import icon from './icon.jsx';

export const name = 'mailpoet-form/email-input';

export const settings = {
  title: MailPoet.I18n.t('blockEmail'),
  description: MailPoet.I18n.t('blockEmailDescription'),
  icon,
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
  edit,
  save() {
    return null;
  },
};
