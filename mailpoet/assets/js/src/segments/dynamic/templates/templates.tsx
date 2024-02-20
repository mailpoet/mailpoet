import { __ } from '@wordpress/i18n';
import {
  SegmentConnectTypes,
  SegmentTemplate,
  SegmentTemplateCategories,
  Timeframe,
} from '../types';
import { WooCommerceActionTypes } from '../dynamic-segments-filters/woocommerce-options';

export const templates: SegmentTemplate[] = [
  {
    name: __('Recently Subscribed', 'mailpoet'),
    slug: 'recently-subscribed',
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have subscribed to your emails within the last 30 days.',
      'mailpoet',
    ),
    isEssential: true,
    filters: [
      {
        segmentType: 'userRole',
        action: 'subscribedDate',
        operator: 'inTheLast',
        value: '30',
      },
    ],
  },
  {
    name: __('Engaged Subscribers (30 days)', 'mailpoet'),
    slug: 'engaged-subscribers-30-days',
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have interacted with your emails or made at least one purchase, and received emails from you in the last 30 days.',
      'mailpoet',
    ),
    isEssential: true,
    filters: [
      {
        segmentType: 'userRole',
        action: 'lastClickDate',
        operator: 'inTheLast',
        value: '30',
      },
      {
        segmentType: 'userRole',
        action: 'lastOpenDate',
        operator: 'inTheLast',
        value: '30',
      },
      {
        segmentType: 'userRole',
        action: 'lastPurchaseDate',
        operator: 'inTheLast',
        value: '30',
      },
      {
        segmentType: 'userRole',
        action: 'lastSendingDate',
        operator: 'inTheLast',
        value: '30',
      },
    ],
    filtersConnect: SegmentConnectTypes.OR,
  },
  {
    name: __('Engaged Subscribers (3 months)', 'mailpoet'),
    slug: 'engaged-subscribers-3-months',
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have interacted with your emails or made at least one purchase, and received emails from you in the last 3 months.',
      'mailpoet',
    ),
    isEssential: true,
    filters: [
      {
        segmentType: 'userRole',
        action: 'lastClickDate',
        operator: 'inTheLast',
        value: '90',
      },
      {
        segmentType: 'userRole',
        action: 'lastOpenDate',
        operator: 'inTheLast',
        value: '90',
      },
      {
        segmentType: 'userRole',
        action: 'lastPurchaseDate',
        operator: 'inTheLast',
        value: '90',
      },
      {
        segmentType: 'userRole',
        action: 'lastSendingDate',
        operator: 'inTheLast',
        value: '90',
      },
    ],
    filtersConnect: SegmentConnectTypes.OR,
  },
  {
    name: __('Engaged Subscribers (6 months)', 'mailpoet'),
    slug: 'engaged-subscribers-6-months',
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have interacted with your emails or made at least one purchase, and received emails from you in the last 6 months.',
      'mailpoet',
    ),
    isEssential: true,
    filters: [
      {
        segmentType: 'userRole',
        action: 'lastClickDate',
        operator: 'inTheLast',
        value: '180',
      },
      {
        segmentType: 'userRole',
        action: 'lastOpenDate',
        operator: 'inTheLast',
        value: '180',
      },
      {
        segmentType: 'userRole',
        action: 'lastPurchaseDate',
        operator: 'inTheLast',
        value: '180',
      },
      {
        segmentType: 'userRole',
        action: 'lastSendingDate',
        operator: 'inTheLast',
        value: '180',
      },
    ],
    filtersConnect: SegmentConnectTypes.OR,
  },
  {
    name: __('Unengaged Subscribers', 'mailpoet'),
    slug: 'unengaged-subscribers',
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who haven’t interacted with your emails, haven’t made a purchase, or haven’t visited your page in the last 6 months.',
      'mailpoet',
    ),
    filters: [
      {
        segmentType: 'userRole',
        action: 'lastEngagementDate',
        operator: 'notInTheLast',
        value: '180',
      },
      {
        segmentType: 'userRole',
        action: 'subscribedDate',
        operator: 'notInTheLast',
        value: '210',
      },
      {
        segmentType: 'email',
        action: 'numberReceived',
        operator: 'more',
        emails: '9',
        timeframe: Timeframe.ALL_TIME,
      },
    ],
    isEssential: true,
  },
  // {
  //   name: __('First-Time Buyers', 'mailpoet'),
  //   slug: 'first-time-buyers',
  //   category: SegmentTemplateCategories.PURCHASE_HISTORY,
  //   description: __(
  //     'Customers who have made their first purchase in the last 30 days.',
  //     'mailpoet',
  //   ),
  //   isEssential: true,
  // },
  {
    name: __('Recent Buyers', 'mailpoet'),
    slug: 'recent-buyers',
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who have made a purchase within the last 30 days. ',
      'mailpoet',
    ),
    isEssential: true,
    filters: [
      {
        segmentType: 'woocommerce',
        action: 'numberOfOrders',
        timeframe: Timeframe.IN_THE_LAST,
        number_of_orders_type: '>',
        number_of_orders_count: 0,
        days: '30',
      },
    ],
  },
  {
    name: __('Repeat Buyers', 'mailpoet'),
    slug: 'repeat-buyers',
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who have made at least two purchases in the last 6 months.',
      'mailpoet',
    ),
    isEssential: true,
    filters: [
      {
        segmentType: 'woocommerce',
        action: 'numberOfOrders',
        timeframe: Timeframe.IN_THE_LAST,
        number_of_orders_type: '>',
        number_of_orders_count: 1,
        days: '180',
      },
    ],
  },
  {
    name: __('Loyal Buyers', 'mailpoet'),
    slug: 'loyal-buyers',
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who have made at least five purchases in the last 12 months.',
      'mailpoet',
    ),
    isEssential: true,
    filters: [
      {
        segmentType: 'woocommerce',
        action: 'numberOfOrders',
        timeframe: Timeframe.IN_THE_LAST,
        number_of_orders_type: '>',
        number_of_orders_count: 4,
        days: '365',
      },
    ],
  },
  {
    name: __('Win-Back', 'mailpoet'),
    slug: 'win-back',
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who have previously purchased, but haven’t made a purchase in the last 6 months.',
      'mailpoet',
    ),
    isEssential: true,
    filters: [
      {
        segmentType: 'userRole',
        action: 'lastPurchaseDate',
        operator: 'notInTheLast',
        value: '180',
      },
    ],
  },
  {
    name: __('Lapsed Customers', 'mailpoet'),
    slug: 'lapsed-customers',
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who haven’t made a purchase in the last 9 months.',
      'mailpoet',
    ),
    isEssential: false,
    filters: [
      {
        segmentType: 'userRole',
        action: 'lastPurchaseDate',
        operator: 'notInTheLast',
        value: '270',
      },
    ],
  },
  // {
  //   name: __('Clickers', 'mailpoet'),
  //   slug: 'clickers',
  //   category: SegmentTemplateCategories.ENGAGEMENT,
  //   description: __(
  //     'Contacts who regularly click on your emails in the last 90 days.',
  //     'mailpoet',
  //   ),
  //   isEssential: false,
  // },
  // {
  //   name: __('Non-Openers', 'mailpoet'),
  //   slug: 'non-openers',
  //   category: SegmentTemplateCategories.ENGAGEMENT,
  //   description: __('Contacts who have received but haven’t opened an email in the last 90 days.', 'mailpoet'),
  //   isEssential: false,
  //   filters: [
  //     {
  //       segmentType: 'email',
  //       action: 'opensAbsoluteCount',
  //       operator: 'equals',
  //       timeframe: Timeframe.IN_THE_LAST,
  //       opens: '0',
  //       days: '90',
  //     },
  //   ],
  // },
  // {
  //   name: __('Recent Clickers', 'mailpoet'),
  //   slug: 'recent-clickers',
  //   category: SegmentTemplateCategories.ENGAGEMENT,
  //   description: __(
  //     'Contacts who have clicked on an email in the last 7 days.',
  //     'mailpoet',
  //   ),
  //   isEssential: false,
  // },
  {
    name: __('Recent Openers', 'mailpoet'),
    slug: 'recent-openers',
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have opened an email in the last 7 days.',
      'mailpoet',
    ),
    isEssential: false,
    filters: [
      {
        segmentType: 'email',
        action: 'opensAbsoluteCount',
        operator: 'more',
        timeframe: Timeframe.IN_THE_LAST,
        opens: '0',
        days: '7',
      },
    ],
  },
  {
    name: __('Big Spenders', 'mailpoet'),
    slug: 'big-spenders',
    category: SegmentTemplateCategories.SHOPPING_BEHAVIOR,
    description: __(
      'Customers who have completed $100 or more worth of orders in the last 12 months.',
      'mailpoet',
    ),
    isEssential: false,
    filters: [
      {
        segmentType: 'woocommerce',
        action: 'totalSpent',
        timeframe: Timeframe.IN_THE_LAST,
        total_spent_type: '>',
        total_spent_amount: 100,
        days: '365',
      },
    ],
  },
  {
    name: __('Used a discount code', 'mailpoet'),
    slug: 'used-a-discount-code',
    category: SegmentTemplateCategories.SHOPPING_BEHAVIOR,
    description: __(
      'Customers who made a purchase with a coupon code in the last 30 days.',
      'mailpoet',
    ),
    filters: [
      {
        segmentType: 'woocommerce',
        action: WooCommerceActionTypes.NUMBER_OF_ORDERS_WITH_COUPON,
        number_of_orders_type: '>',
        number_of_orders_count: 0,
        timeframe: Timeframe.IN_THE_LAST,
        days: '30',
      },
    ],
    isEssential: false,
  },
  {
    name: __('Frequently uses discounts', 'mailpoet'),
    slug: 'frequently-uses-discounts',
    category: SegmentTemplateCategories.SHOPPING_BEHAVIOR,
    description: __(
      'Customers who have regularly used coupons in the last 90 days.',
      'mailpoet',
    ),
    filters: [
      {
        segmentType: 'woocommerce',
        action: WooCommerceActionTypes.NUMBER_OF_ORDERS_WITH_COUPON,
        number_of_orders_type: '>',
        number_of_orders_count: 2,
        timeframe: Timeframe.IN_THE_LAST,
        days: '90',
      },
    ],
    isEssential: false,
  },
];

export const templateCategories = [
  {
    slug: SegmentTemplateCategories.ENGAGEMENT,
    name: __('Engagement', 'mailpoet'),
  },
  {
    slug: SegmentTemplateCategories.PURCHASE_HISTORY,
    name: __('Purchase History', 'mailpoet'),
  },
  {
    slug: SegmentTemplateCategories.SHOPPING_BEHAVIOR,
    name: __('Shopping Behavior', 'mailpoet'),
  },
];

export function getCategoryNameBySlug(slug) {
  const foundCategory = templateCategories.find(
    (category) => category.slug === slug,
  );
  return foundCategory ? foundCategory.name : null;
}
