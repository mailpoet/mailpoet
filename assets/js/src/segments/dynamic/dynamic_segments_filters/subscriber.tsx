import MailPoet from 'mailpoet';

import {
  WordpressRoleFormItem,
  SegmentTypes,
} from '../types';

export function validateSubscriber(formItems: WordpressRoleFormItem): boolean {
  return !!formItems.wordpressRole;
}

export const SubscriberSegmentOptions = [
  { value: 'wordpressRole', label: MailPoet.I18n.t('segmentsSubscriber'), group: SegmentTypes.WordPressRole },
  { value: 'subscribedDate', label: MailPoet.I18n.t('subscribedDate'), group: SegmentTypes.WordPressRole },
];
