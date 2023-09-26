import { MailPoet } from 'mailpoet';
import { defaultBlockStyles } from 'form_editor/store/mapping/to_blocks/styles_mapper';
import { Icon } from './icon.jsx';
import { LastNameEdit } from './edit.jsx';

export const name = 'mailpoet-form/last-name-input';

export const settings = {
  title: MailPoet.I18n.t('blockLastName'),
  description: MailPoet.I18n.t('blockLastNameDescription'),
  icon: Icon,
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
      default: false,
    },
    styles: {
      type: 'object',
      default: defaultBlockStyles,
    },
  },
  supports: {
    html: false,
    multiple: false,
  },
  edit: LastNameEdit,
  save() {
    return null;
  },
};
