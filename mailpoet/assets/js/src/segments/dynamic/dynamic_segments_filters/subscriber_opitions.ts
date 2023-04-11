import { MailPoet } from 'mailpoet';
import { SegmentTypes, SubscriberActionTypes } from '../types';

export const SubscriberSegmentOptions = [
  {
    value: SubscriberActionTypes.SUBSCRIBER_SCORE,
    label: MailPoet.I18n.t('subscriberScore'),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.MAILPOET_CUSTOM_FIELD,
    label: MailPoet.I18n.t('mailpoetCustomField'),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBED_DATE,
    label: MailPoet.I18n.t('subscribedDate'),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBED_TO_LIST,
    label: MailPoet.I18n.t('subscribedToList'),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBER_TAG,
    label: MailPoet.I18n.t('subscriberTag'),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.WORDPRESS_ROLE,
    label: MailPoet.I18n.t('segmentsSubscriber'),
    group: SegmentTypes.WordPressRole,
  },
];
