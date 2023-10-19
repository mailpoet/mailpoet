import { MailPoet } from 'mailpoet';
import { sortFilters } from './sort-filters';
import { SegmentTypes } from '../types';

// WooCommerce
export enum WooCommerceActionTypes {
  NUMBER_OF_ORDERS = 'numberOfOrders',
  NUMBER_OF_REVIEWS = 'numberOfReviews',
  PURCHASED_CATEGORY = 'purchasedCategory',
  PURCHASE_DATE = 'purchaseDate',
  PURCHASED_PRODUCT = 'purchasedProduct',
  TOTAL_SPENT = 'totalSpent',
  AVERAGE_SPENT = 'averageSpent',
  CUSTOMER_IN_COUNTRY = 'customerInCountry',
  CUSTOMER_IN_CITY = 'customerInCity',
  CUSTOMER_IN_POSTAL_CODE = 'customerInPostalCode',
  SINGLE_ORDER_VALUE = 'singleOrderValue',
  USED_COUPON_CODE = 'usedCouponCode',
  USED_PAYMENT_METHOD = 'usedPaymentMethod',
  USED_SHIPPING_METHOD = 'usedShippingMethod',
}

export const WooCommerceOptions = [
  {
    value: WooCommerceActionTypes.AVERAGE_SPENT,
    label: MailPoet.I18n.t('wooAverageSpent'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.CUSTOMER_IN_CITY,
    label: MailPoet.I18n.t('wooCustomerInCity'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.CUSTOMER_IN_COUNTRY,
    label: MailPoet.I18n.t('wooCustomerInCountry'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.CUSTOMER_IN_POSTAL_CODE,
    label: MailPoet.I18n.t('wooCustomerInPostalCode'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.NUMBER_OF_ORDERS,
    label: MailPoet.I18n.t('wooNumberOfOrders'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.NUMBER_OF_REVIEWS,
    label: MailPoet.I18n.t('wooNumberOfReviews'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.PURCHASED_CATEGORY,
    label: MailPoet.I18n.t('wooPurchasedCategory'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.PURCHASE_DATE,
    label: MailPoet.I18n.t('wooPurchaseDate'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.PURCHASED_PRODUCT,
    label: MailPoet.I18n.t('wooPurchasedProduct'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.SINGLE_ORDER_VALUE,
    label: MailPoet.I18n.t('wooSingleOrderValue'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.TOTAL_SPENT,
    label: MailPoet.I18n.t('wooTotalSpent'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.USED_COUPON_CODE,
    label: MailPoet.I18n.t('wooUsedCouponCode'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.USED_PAYMENT_METHOD,
    label: MailPoet.I18n.t('wooUsedPaymentMethod'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.USED_SHIPPING_METHOD,
    label: MailPoet.I18n.t('wooUsedShippingMethod'),
    group: SegmentTypes.WooCommerce,
  },
].sort(sortFilters);

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
].sort(sortFilters);

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
].sort(sortFilters);
