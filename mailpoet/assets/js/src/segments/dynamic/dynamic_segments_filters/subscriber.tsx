import { useSelect } from '@wordpress/data';

import { WordpressRoleFormItem, SubscriberActionTypes } from '../types';
import { storeName } from '../store';
import { WordpressRoleFields } from './subscriber_wordpress_role';
import {
  SubscriberScoreFields,
  validateSubscriberScore,
} from './subscriber_score';
import { DateFields, DateOperator } from './date_fields';
import {
  MailPoetCustomFields,
  validateMailPoetCustomField,
} from './subscriber_mailpoet_custom_field';
import {
  SubscribedToList,
  validateSubscribedToList,
} from './subscriber_subscribed_to_list';
import { SubscriberTag, validateSubscriberTag } from './subscriber_tag';

export function validateSubscriber(formItems: WordpressRoleFormItem): boolean {
  if (
    !formItems.action ||
    formItems.action === SubscriberActionTypes.WORDPRESS_ROLE
  ) {
    return (
      Array.isArray(formItems.wordpressRole) &&
      formItems.wordpressRole.length > 0
    );
  }
  if (formItems.action === SubscriberActionTypes.MAILPOET_CUSTOM_FIELD) {
    return validateMailPoetCustomField(formItems);
  }
  if (formItems.action === SubscriberActionTypes.SUBSCRIBER_SCORE) {
    return validateSubscriberScore(formItems);
  }
  if (formItems.action === SubscriberActionTypes.SUBSCRIBED_TO_LIST) {
    return validateSubscribedToList(formItems);
  }
  if (formItems.action === SubscriberActionTypes.SUBSCRIBER_TAG) {
    return validateSubscriberTag(formItems);
  }
  if (!formItems.operator || !formItems.value) {
    return false;
  }
  if (
    formItems.operator === DateOperator.BEFORE ||
    formItems.operator === DateOperator.AFTER ||
    formItems.operator === DateOperator.ON ||
    formItems.operator === DateOperator.NOT_ON
  ) {
    const re = /^\d+-\d+-\d+$/;
    return re.test(formItems.value);
  }
  if (
    formItems.operator === DateOperator.IN_THE_LAST ||
    formItems.operator === DateOperator.NOT_IN_THE_LAST
  ) {
    const re = /^\d+$/;
    return re.test(formItems.value) && Number(formItems.value) > 0;
  }
  return false;
}

const componentsMap = {
  [SubscriberActionTypes.WORDPRESS_ROLE]: WordpressRoleFields,
  [SubscriberActionTypes.SUBSCRIBER_SCORE]: SubscriberScoreFields,
  [SubscriberActionTypes.SUBSCRIBED_DATE]: DateFields,
  [SubscriberActionTypes.MAILPOET_CUSTOM_FIELD]: MailPoetCustomFields,
  [SubscriberActionTypes.SUBSCRIBED_TO_LIST]: SubscribedToList,
  [SubscriberActionTypes.SUBSCRIBER_TAG]: SubscriberTag,
};

type Props = {
  filterIndex: number;
};

export function SubscriberFields({ filterIndex }: Props): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  let Component;
  if (!segment.action) {
    Component = WordpressRoleFields;
  } else {
    Component = componentsMap[segment.action];
  }

  if (!Component) return null;

  return <Component filterIndex={filterIndex} />;
}
