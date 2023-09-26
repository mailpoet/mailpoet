import { MailPoet } from 'mailpoet';
import { Icon } from './icon.jsx';
import { DividerEdit } from './edit';
import { defaultAttributes } from './divider-types';

export const name = 'mailpoet-form/divider';

export const settings = {
  title: MailPoet.I18n.t('blockDivider'),
  description: null,
  icon: Icon,
  category: 'design',
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
  edit: DividerEdit,
  save(): null {
    return null;
  },
};
