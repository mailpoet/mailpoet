import { MailPoet } from 'mailpoet';
import { Icon } from './icon.jsx';
import { SegmentSelectEdit } from './edit.jsx';

export const name = 'mailpoet-form/segment-select';

export const settings = {
  title: MailPoet.I18n.t('blockSegmentSelect'),
  description: MailPoet.I18n.t('blockLastNameDescription'),
  icon: Icon,
  category: 'fields',
  attributes: {
    label: {
      type: 'string',
      default: MailPoet.I18n.t('blockSegmentSelectLabel'),
    },
    values: {
      type: 'array',
      default: [],
    },
  },
  supports: {
    html: false,
    multiple: false,
  },
  edit: SegmentSelectEdit,
  save() {
    return null;
  },
};
