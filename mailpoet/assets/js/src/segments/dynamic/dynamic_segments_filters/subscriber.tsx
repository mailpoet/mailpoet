import { useSelect } from '@wordpress/data';

import {
  FilterProps,
  SubscriberActionTypes,
  WordpressRoleFormItem,
} from '../types';
import { storeName } from '../store';
import { WordpressRoleFields } from './fields/subscriber/subscriber_wordpress_role';
import {
  SubscriberScoreFields,
  validateSubscriberScore,
} from './fields/subscriber/subscriber_score';
import {
  DateFields,
  DateOperator,
  validateDateField,
} from './fields/date_fields';
import {
  MailPoetCustomFields,
  validateMailPoetCustomField,
} from './fields/subscriber/subscriber_mailpoet_custom_field';
import {
  SubscribedToList,
  validateSubscribedToList,
} from './fields/subscriber/subscriber_subscribed_to_list';
import {
  SubscriberTag,
  validateSubscriberTag,
} from './fields/subscriber/subscriber_tag';
import { TextField, validateTextField } from './fields/text_field';
import {
  SubscribedViaForm,
  validateSubscribedViaForm,
} from './fields/subscriber/subscribed_via_form';

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
  if (
    [
      SubscriberActionTypes.SUBSCRIBER_FIRST_NAME,
      SubscriberActionTypes.SUBSCRIBER_LAST_NAME,
      SubscriberActionTypes.SUBSCRIBER_EMAIL,
    ].includes(formItems.action as SubscriberActionTypes)
  ) {
    return validateTextField(formItems);
  }
  if (
    [
      SubscriberActionTypes.SUBSCRIBER_LAST_ENGAGEMENT_DATE,
      SubscriberActionTypes.SUBSCRIBER_LAST_PURCHASE_DATE,
      SubscriberActionTypes.SUBSCRIBER_LAST_OPEN_DATE,
      SubscriberActionTypes.SUBSCRIBER_LAST_CLICK_DATE,
      SubscriberActionTypes.SUBSCRIBER_LAST_PAGE_VIEW_DATE,
      SubscriberActionTypes.SUBSCRIBER_LAST_SENDING_DATE,
    ].includes(formItems.action as SubscriberActionTypes)
  ) {
    return validateDateField(formItems);
  }

  if (formItems.action === SubscriberActionTypes.SUBSCRIBED_VIA_FORM) {
    return validateSubscribedViaForm(formItems);
  }
  if (!formItems.operator || !formItems.value) {
    return false;
  }
  if (
    Object.values(DateOperator).includes(formItems.operator as DateOperator)
  ) {
    return validateDateField(formItems);
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
  [SubscriberActionTypes.SUBSCRIBER_FIRST_NAME]: TextField,
  [SubscriberActionTypes.SUBSCRIBER_LAST_NAME]: TextField,
  [SubscriberActionTypes.SUBSCRIBER_EMAIL]: TextField,
  [SubscriberActionTypes.SUBSCRIBED_VIA_FORM]: SubscribedViaForm,
  [SubscriberActionTypes.SUBSCRIBER_LAST_ENGAGEMENT_DATE]: DateFields,
  [SubscriberActionTypes.SUBSCRIBER_LAST_PURCHASE_DATE]: DateFields,
  [SubscriberActionTypes.SUBSCRIBER_LAST_OPEN_DATE]: DateFields,
  [SubscriberActionTypes.SUBSCRIBER_LAST_CLICK_DATE]: DateFields,
  [SubscriberActionTypes.SUBSCRIBER_LAST_PAGE_VIEW_DATE]: DateFields,
  [SubscriberActionTypes.SUBSCRIBER_LAST_SENDING_DATE]: DateFields,
};

export function SubscriberFields({ filterIndex }: FilterProps): JSX.Element {
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
