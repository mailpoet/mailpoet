import MailPoet from 'mailpoet';
import icon from './icon.jsx';
import edit from './edit';

export enum Types {
  Divider = 'divider',
  Spacer = 'spacer',
}

export enum Style {
  Solid = 'solid',
  Dashed = 'dashed',
  Dotted = 'dotted',
}

export const name = 'mailpoet-form/divider';

export const settings = {
  title: MailPoet.I18n.t('blockDivider'),
  description: null,
  icon,
  category: 'layout',
  attributes: {
    height: {
      type: 'number',
      default: 1,
    },
    type: {
      type: 'string',
      default: Types.Divider,
    },
    style: {
      type: 'string',
      default: Style.Solid,
    },
    dividerHeight: {
      type: 'number',
      default: 1,
    },
    dividerWidth: {
      type: 'number',
      default: 100,
    },
    color: {
      type: 'string',
    },
  },
  supports: {
    html: false,
    multiple: true,
  },
  edit,
  save() {
    return null;
  },
};
