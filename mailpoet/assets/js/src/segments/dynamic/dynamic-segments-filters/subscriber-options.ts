import { MailPoet } from 'mailpoet';
import { sortFilters } from './sort-filters';
import { SegmentTypes, SubscriberActionTypes } from '../types';

export const SubscriberSegmentOptions = [
  {
    value: SubscriberActionTypes.SUBSCRIBER_EMAIL,
    label: MailPoet.I18n.t('email').toLowerCase(),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBER_SCORE,
    label: MailPoet.I18n.t('subscriberScore'),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBER_FIRST_NAME,
    label: MailPoet.I18n.t('firstName').toLowerCase(),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBER_LAST_NAME,
    label: MailPoet.I18n.t('lastName').toLowerCase(),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBER_LAST_CLICK_DATE,
    label: MailPoet.I18n.t('lastClickDate').toLowerCase(),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBER_LAST_ENGAGEMENT_DATE,
    label: MailPoet.I18n.t('lastEngagementDate').toLowerCase(),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBER_LAST_OPEN_DATE,
    label: MailPoet.I18n.t('lastOpenDate').toLowerCase(),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBER_LAST_PAGE_VIEW_DATE,
    label: MailPoet.I18n.t('lastPageViewDate').toLowerCase(),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBER_LAST_PURCHASE_DATE,
    label: MailPoet.I18n.t('lastPurchaseDate').toLowerCase(),
    group: SegmentTypes.WordPressRole,
  },
  {
    value: SubscriberActionTypes.SUBSCRIBER_LAST_SENDING_DATE,
    label: MailPoet.I18n.t('lastSendingDate').toLowerCase(),
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
    value: SubscriberActionTypes.SUBSCRIBED_VIA_FORM,
    label: MailPoet.I18n.t('subscribedViaForm'),
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
].sort(sortFilters);
