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

export interface Attributes {
  className: string,
  height: number;
  type: Types;
  style: Style;
  dividerHeight: number;
  dividerWidth: number;
  color: string;
}

export const defaultAttributes = {
  className: undefined,
  height: 1,
  type: Types.Divider,
  style: Style.Solid,
  dividerHeight: 1,
  dividerWidth: 100,
  color: 'black',
};

export const name = 'mailpoet-form/divider';

export const settings = {
  title: MailPoet.I18n.t('blockDivider'),
  description: null,
  icon,
  category: 'layout',
  attributes: {
    height: {
      type: 'number',
      default: defaultAttributes.height,
    },
    type: {
      type: 'string',
      default: defaultAttributes.type,
    },
    style: {
      type: 'string',
      default: defaultAttributes.style,
    },
    dividerHeight: {
      type: 'number',
      default: defaultAttributes.dividerHeight,
    },
    dividerWidth: {
      type: 'number',
      default: defaultAttributes.dividerWidth,
    },
    color: {
      type: 'string',
      default: defaultAttributes.color,
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
