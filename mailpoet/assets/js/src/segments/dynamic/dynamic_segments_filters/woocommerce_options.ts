import { MailPoet } from 'mailpoet';
import { SegmentTypes } from '../types';

// WooCommerce
export enum WooCommerceActionTypes {
  NUMBER_OF_ORDERS = 'numberOfOrders',
  PURCHASED_CATEGORY = 'purchasedCategory',
  PURCHASED_PRODUCT = 'purchasedProduct',
  TOTAL_SPENT = 'totalSpent',
  CUSTOMER_IN_COUNTRY = 'customerInCountry',
}

export const WooCommerceOptions = [
  {
    value: WooCommerceActionTypes.CUSTOMER_IN_COUNTRY,
    label: MailPoet.I18n.t('wooCustomerInCountry'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.NUMBER_OF_ORDERS,
    label: MailPoet.I18n.t('wooNumberOfOrders'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.PURCHASED_CATEGORY,
    label: MailPoet.I18n.t('wooPurchasedCategory'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.PURCHASED_PRODUCT,
    label: MailPoet.I18n.t('wooPurchasedProduct'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.TOTAL_SPENT,
    label: MailPoet.I18n.t('wooTotalSpent'),
    group: SegmentTypes.WooCommerce,
  },
];

export const actionTypesWithDefaultTypeAny: Array<string> = [
  WooCommerceActionTypes.PURCHASED_PRODUCT,
  WooCommerceActionTypes.PURCHASED_CATEGORY,
];

// WooCommerce Memberships
export enum WooCommerceMembershipsActionTypes {
  MEMBER_OF = 'isMemberOf',
}

export const WooCommerceMembershipOptions = [
  {
    value: WooCommerceMembershipsActionTypes.MEMBER_OF,
    label: MailPoet.I18n.t('segmentsActiveMembership'),
    group: SegmentTypes.WooCommerceMembership,
  },
];

// WooCommerce Subscriptions
export enum WooCommerceSubscriptionsActionTypes {
  ACTIVE_SUBSCRIPTIONS = 'hasActiveSubscription',
}

export const WooCommerceSubscriptionOptions = [
  {
    value: WooCommerceSubscriptionsActionTypes.ACTIVE_SUBSCRIPTIONS,
    label: MailPoet.I18n.t('segmentsActiveSubscription'),
    group: SegmentTypes.WooCommerceSubscription,
  },
];
