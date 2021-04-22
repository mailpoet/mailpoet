import MailPoet from 'mailpoet';

import {
  WordpressRoleFormItem,
  SegmentTypes,
} from '../types';

export function validateWordpressRole(formItems: WordpressRoleFormItem): boolean {
  return !!formItems.wordpressRole;
}

export const WordpressRoleSegmentOptions = [
  { value: 'wordpressRole', label: MailPoet.I18n.t('segmentsSubscriber'), group: SegmentTypes.WordPressRole },
  { value: 'wordpressRole', label: MailPoet.I18n.t('segmentsSubscriber'), group: SegmentTypes.WordPressRole },
];
