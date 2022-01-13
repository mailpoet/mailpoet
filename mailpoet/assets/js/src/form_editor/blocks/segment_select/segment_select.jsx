import MailPoet from 'mailpoet';
import icon from './icon.jsx';
import edit from './edit.jsx';

export const name = 'mailpoet-form/segment-select';

export const settings = {
  title: MailPoet.I18n.t('blockSegmentSelect'),
  description: MailPoet.I18n.t('blockLastNameDescription'),
  icon,
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
  edit,
  save() {
    return null;
  },
};
