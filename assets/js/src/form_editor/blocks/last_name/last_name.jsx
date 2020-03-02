import MailPoet from 'mailpoet';
import icon from './icon.jsx';
import edit from './edit.jsx';
import { defaultBlockStyles } from '../../store/form_body_to_blocks.jsx';

export const name = 'mailpoet-form/last-name-input';

export const settings = {
  title: MailPoet.I18n.t('blockLastName'),
  description: MailPoet.I18n.t('blockLastNameDescription'),
  icon,
  category: 'fields',
  attributes: {
    label: {
      type: 'string',
      default: MailPoet.I18n.t('blockLastName'),
    },
    labelWithinInput: {
      type: 'boolean',
      default: true,
    },
    mandatory: {
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
    customClassName: false,
    multiple: false,
  },
  edit,
  save() {
    return null;
  },
};
