import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect } from '@wordpress/data';

import {
  SegmentTypes,
  WordpressRoleFormItem,
  SubscriberActionTypes,
} from '../types';
import { WordpressRoleFields } from './subscriber_wordpress_role';
import { SubscriberScoreFields, validateSubscriberScore } from './subscriber_score';
import { SubscribedDateFields, SubscribedDateOperator } from './subscriber_subscribed_date';
import { MailPoetCustomFields, validateMailPoetCustomField } from './subscriber_mailpoet_custom_field';
import { SubscribedToList } from './subscriber_subscribed_to_list';

export function validateSubscriber(formItems: WordpressRoleFormItem): boolean {
  if ((!formItems.action) || (formItems.action === SubscriberActionTypes.WORDPRESS_ROLE)) {
    return !!formItems.wordpressRole;
  }
  if (formItems.action === SubscriberActionTypes.MAILPOET_CUSTOM_FIELD) {
    return validateMailPoetCustomField(formItems);
  }
  if (formItems.action === SubscriberActionTypes.SUBSCRIBER_SCORE) {
    return validateSubscriberScore(formItems);
  }
  if (!formItems.operator || !formItems.value) {
    return false;
  }
  if (
    formItems.operator === SubscribedDateOperator.BEFORE
    || formItems.operator === SubscribedDateOperator.AFTER
  ) {
    const re = new RegExp(/^\d+-\d+-\d+$/);
    return re.test(formItems.value);
  }
  if (
    formItems.operator === SubscribedDateOperator.IN_THE_LAST
    || formItems.operator === SubscribedDateOperator.NOT_IN_THE_LAST
  ) {
    const re = new RegExp(/^\d+$/);
    return re.test(formItems.value) && (Number(formItems.value) > 0);
  }
  return false;
}

export const SubscriberSegmentOptions = [
  { value: SubscriberActionTypes.MAILPOET_CUSTOM_FIELD, label: MailPoet.I18n.t('mailpoetCustomField'), group: SegmentTypes.WordPressRole },
  { value: SubscriberActionTypes.SUBSCRIBER_SCORE, label: MailPoet.I18n.t('subscriberScore'), group: SegmentTypes.WordPressRole },
  { value: SubscriberActionTypes.SUBSCRIBED_DATE, label: MailPoet.I18n.t('subscribedDate'), group: SegmentTypes.WordPressRole },
  { value: SubscriberActionTypes.SUBSCRIBED_TO_LIST, label: MailPoet.I18n.t('subscribedToList'), group: SegmentTypes.WordPressRole },
  { value: SubscriberActionTypes.WORDPRESS_ROLE, label: MailPoet.I18n.t('segmentsSubscriber'), group: SegmentTypes.WordPressRole },
];

const componentsMap = {
  [SubscriberActionTypes.WORDPRESS_ROLE]: WordpressRoleFields,
  [SubscriberActionTypes.SUBSCRIBER_SCORE]: SubscriberScoreFields,
  [SubscriberActionTypes.SUBSCRIBED_DATE]: SubscribedDateFields,
  [SubscriberActionTypes.MAILPOET_CUSTOM_FIELD]: MailPoetCustomFields,
  [SubscriberActionTypes.SUBSCRIBED_TO_LIST]: SubscribedToList,
};

type Props = {
  filterIndex: number;
}

export const SubscriberFields: React.FunctionComponent<Props> = ({ filterIndex }) => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex]
  );

  let Component;
  if (!segment.action) {
    Component = WordpressRoleFields;
  } else {
    Component = componentsMap[segment.action];
  }

  if (!Component) return null;

  return (
    <Component filterIndex={filterIndex} />
  );
};
