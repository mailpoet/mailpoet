import { __ } from '@wordpress/i18n';
import { sortFilters } from './sort-filters';
import { SegmentTypes } from '../types';

// WooCommerce
export enum WooCommerceActionTypes {
  NUMBER_OF_ORDERS = 'numberOfOrders',
  NUMBER_OF_ORDERS_WITH_COUPON = 'numberOfOrdersWithCoupon',
  NUMBER_OF_REVIEWS = 'numberOfReviews',
  PURCHASED_CATEGORY = 'purchasedCategory',
  PURCHASE_DATE = 'purchaseDate',
  PURCHASED_PRODUCT = 'purchasedProduct',
  PURCHASED_WITH_ATTRIBUTE = 'purchasedWithAttribute',
  TOTAL_SPENT = 'totalSpent',
  AVERAGE_SPENT = 'averageSpent',
  CUSTOMER_IN_COUNTRY = 'customerInCountry',
  CUSTOMER_IN_CITY = 'customerInCity',
  CUSTOMER_IN_POSTAL_CODE = 'customerInPostalCode',
  SINGLE_ORDER_VALUE = 'singleOrderValue',
  USED_COUPON_CODE = 'usedCouponCode',
  USED_PAYMENT_METHOD = 'usedPaymentMethod',
  USED_SHIPPING_METHOD = 'usedShippingMethod',
  FIRST_ORDER = 'firstOrder',
}

export const WooCommerceOptions = [
  {
    value: WooCommerceActionTypes.AVERAGE_SPENT,
    label: __('average order value', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.CUSTOMER_IN_CITY,
    label: __('city', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.CUSTOMER_IN_COUNTRY,
    label: __('country', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.FIRST_ORDER,
    label: __('first order', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.NUMBER_OF_ORDERS,
    label: __('number of orders', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.NUMBER_OF_ORDERS_WITH_COUPON,
    label: __('number of orders with coupon code', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.NUMBER_OF_REVIEWS,
    label: __('number of reviews', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.CUSTOMER_IN_POSTAL_CODE,
    label: __('postal code', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.PURCHASED_CATEGORY,
    label: __('purchased in category', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.PURCHASE_DATE,
    label: __('purchase date', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.PURCHASED_PRODUCT,
    label: __('purchased product', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.PURCHASED_WITH_ATTRIBUTE,
    label: __('purchased with attribute', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.SINGLE_ORDER_VALUE,
    label: __('single order value', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.TOTAL_SPENT,
    label: __('total spent', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.USED_COUPON_CODE,
    label: __('used coupon code', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.USED_PAYMENT_METHOD,
    label: __('used payment method', 'mailpoet'),
    group: SegmentTypes.WooCommerce,
  },
  {
    value: WooCommerceActionTypes.USED_SHIPPING_METHOD,
    label: __('used shipping method', 'mailpoet'),
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
    label: __('is member of', 'mailpoet'),
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
    label: __('has active subscription', 'mailpoet'),
    group: SegmentTypes.WooCommerceSubscription,
  },
].sort(sortFilters);
