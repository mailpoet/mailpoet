import { __ } from '@wordpress/i18n';
import { getAvailableFilters } from './all-available-filters';
import {
  SegmentConnectTypes,
  SegmentFormDataWindow,
  SegmentTypes,
  StateType,
  SubscriberActionTypes,
} from '../types';

declare let window: SegmentFormDataWindow;

export function getSegmentInitialState() {
  return {
    filters_connect: SegmentConnectTypes.AND,
    filters: [
      {
        segmentType: SegmentTypes.WordPressRole,
        action: SubscriberActionTypes.WORDPRESS_ROLE,
      },
    ],
  };
}

export const getInitialState = (): StateType => ({
  automations: window.mailpoet_automations,
  products: window.mailpoet_products,
  staticSegmentsList: window.mailpoet_static_segments_list,
  membershipPlans: window.mailpoet_membership_plans,
  subscriptionProducts: window.mailpoet_subscription_products,
  productAttributes: window.mailpoet_product_attributes,
  localProductAttributes: window.mailpoet_local_product_attributes,
  productCategories: window.mailpoet_product_categories,
  productTags: window.mailpoet_product_tags,
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
  segment: getSegmentInitialState(),
  subscriberCount: {
    loading: false,
  },
  errors: [],
  allAvailableFilters: getAvailableFilters(
    window.mailpoet_can_use_woocommerce_subscriptions,
    window.mailpoet_can_use_woocommerce_memberships,
  ),
  previousPage: '',
  dynamicSegmentsQuery: null,
  dynamicSegments: {
    data: null,
    meta: {
      all: 0,
      groups: [
        {
          name: 'all',
          label: __('All', 'mailpoet'),
          count: 0,
        },
        {
          name: 'trash',
          label: __('Trash', 'mailpoet'),
          count: 0,
        },
      ],
    },
  },
});
