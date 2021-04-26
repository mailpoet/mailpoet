import React from 'react';
import MailPoet from 'mailpoet';

import {
  SegmentTypes,
  WordpressRoleFormItem,
  OnFilterChange,
  SubscriberActionTypes,
} from '../types';
import { WordpressRoleFields } from './subscriber_wordpress_role';
import { SubscribedDateFields } from './subscriber_subscribed_date';

export function validateSubscriber(formItems: WordpressRoleFormItem): boolean {
  if ((!formItems.action) || (formItems.action === SubscriberActionTypes.WORDPRESS_ROLE)) {
    return !!formItems.wordpressRole;
  }
  return (!!formItems.operator && !!formItems.value);
}

export const SubscriberSegmentOptions = [
  { value: SubscriberActionTypes.WORDPRESS_ROLE, label: MailPoet.I18n.t('segmentsSubscriber'), group: SegmentTypes.WordPressRole },
  { value: SubscriberActionTypes.SUBSCRIBED_DATE, label: MailPoet.I18n.t('subscribedDate'), group: SegmentTypes.WordPressRole },
];

const componentsMap = {
  [SubscriberActionTypes.WORDPRESS_ROLE]: WordpressRoleFields,
  [SubscriberActionTypes.SUBSCRIBED_DATE]: SubscribedDateFields,
};

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

export const SubscriberFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
  let Component;
  if (!item.action) {
    Component = WordpressRoleFields;
  } else {
    Component = componentsMap[item.action];
  }

  if (!Component) return null;

  return (
    <Component
      item={item}
      onChange={onChange}
    />
  );
};
