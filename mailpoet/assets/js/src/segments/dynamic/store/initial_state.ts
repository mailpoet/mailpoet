import { getAvailableFilters } from './all_available_filters';
import {
  SegmentConnectTypes,
  SegmentFormDataWindow,
  SegmentTypes,
  StateType,
  SubscriberActionTypes,
} from '../types';

declare let window: SegmentFormDataWindow;

export const getInitialState = (): StateType => ({
  products: window.mailpoet_products,
  staticSegmentsList: window.mailpoet_static_segments_list,
  membershipPlans: window.mailpoet_membership_plans,
  subscriptionProducts: window.mailpoet_subscription_products,
  productCategories: window.mailpoet_product_categories,
  newslettersList: window.mailpoet_newsletters_list,
  wordpressRoles: window.wordpress_editable_roles_list,
  canUseWooMemberships: window.mailpoet_can_use_woocommerce_memberships,
  canUseWooSubscriptions: window.mailpoet_can_use_woocommerce_subscriptions,
  wooCurrencySymbol: window.mailpoet_woocommerce_currency_symbol,
  wooCountries: window.mailpoet_woocommerce_countries,
  wooPaymentMethods: window.mailpoet_woocommerce_payment_methods,
  wooShippingMethods: window.mailpoet_woocommerce_shipping_methods,
  customFieldsList: window.mailpoet_custom_fields,
  tags: window.mailpoet_tags,
  signupForms: window.mailpoet_signup_forms,
  segment: {
    filters_connect: SegmentConnectTypes.AND,
    filters: [
      {
        segmentType: SegmentTypes.WordPressRole,
        action: SubscriberActionTypes.WORDPRESS_ROLE,
      },
    ],
  },
  subscriberCount: {
    loading: false,
  },
  errors: [],
  allAvailableFilters: getAvailableFilters(
    window.mailpoet_can_use_woocommerce_subscriptions,
    window.mailpoet_can_use_woocommerce_memberships,
  ),
});
