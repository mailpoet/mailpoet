import { __ } from '@wordpress/i18n';
import { SegmentTemplate, SegmentTemplateCategories } from '../types';

export const templates: SegmentTemplate[] = [
  {
    name: __('Recently Subscribed', 'mailpoet'),
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have subscribed to your emails within the last 30 days.',
      'mailpoet',
    ),
    isEssential: true,
  },
  {
    name: __('Engaged Subscribers (30 days)', 'mailpoet'),
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have interacted with your emails or made at least one purchase, and received emails from you in the last 30 days.',
      'mailpoet',
    ),
    isEssential: true,
  },
  {
    name: __('Engaged Subscribers (3 months)', 'mailpoet'),
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have interacted with your emails or made at least one purchase, and received emails from you in the last 3 months.',
      'mailpoet',
    ),
    isEssential: true,
  },
  {
    name: __('Engaged Subscribers (6 months)', 'mailpoet'),
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have interacted with your emails or made at least one purchase, and received emails from you in the last 6 months.',
      'mailpoet',
    ),
    isEssential: true,
  },
  {
    name: __('Unengaged Subscribers', 'mailpoet'),
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who haven’t interacted with your emails, haven’t made a purchase, or haven’t visited your page in the last 6 months.',
      'mailpoet',
    ),
    isEssential: true,
  },
  {
    name: __('First-Time Buyers', 'mailpoet'),
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who have made their first purchase in the last 30 days.',
      'mailpoet',
    ),
    isEssential: true,
  },
  {
    name: __('Recent Buyers', 'mailpoet'),
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who have made a purchase within the last 30 days. ',
      'mailpoet',
    ),
    isEssential: true,
  },
  {
    name: __('Repeat Buyers', 'mailpoet'),
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who have made at least two purchases in the last 6 months.',
      'mailpoet',
    ),
    isEssential: true,
  },
  {
    name: __('Loyal Buyers', 'mailpoet'),
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who have made at least five purchases in the last 12 months.',
      'mailpoet',
    ),
    isEssential: true,
  },
  {
    name: __('Win-Back', 'mailpoet'),
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who have previously purchased, but haven’t made a purchase in the last 6 months.',
      'mailpoet',
    ),
    isEssential: true,
  },
  {
    name: __('Lapsed Customers', 'mailpoet'),
    category: SegmentTemplateCategories.PURCHASE_HISTORY,
    description: __(
      'Customers who haven’t made a purchase in the last 9 months.',
      'mailpoet',
    ),
    isEssential: false,
  },
  {
    name: __('Clickers', 'mailpoet'),
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who regularly click on your emails in the last 90 days.',
      'mailpoet',
    ),
    isEssential: false,
  },
  {
    name: __('Non-Openers', 'mailpoet'),
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have received but haven’t opened an email in the last 90 days.',
      'mailpoet',
    ),
    isEssential: false,
  },
  {
    name: __('Recent Clickers', 'mailpoet'),
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have clicked on an email in the last 7 days.',
      'mailpoet',
    ),
    isEssential: false,
  },
  {
    name: __('Recent Openers', 'mailpoet'),
    category: SegmentTemplateCategories.ENGAGEMENT,
    description: __(
      'Contacts who have opened an email in the last 7 days.',
      'mailpoet',
    ),
    isEssential: false,
  },
  {
    name: __('Big Spenders', 'mailpoet'),
    category: SegmentTemplateCategories.SHOPPING_BEHAVIOR,
    description: __(
      'Customers who have completed $100 or more worth of orders in the last 12 months.',
      'mailpoet',
    ),
    isEssential: false,
  },
  {
    name: __('Used a discount code', 'mailpoet'),
    category: SegmentTemplateCategories.SHOPPING_BEHAVIOR,
    description: __(
      'Customers who made a purchase with a coupon code in the last 30 days.',
      'mailpoet',
    ),
    isEssential: false,
  },
  {
    name: __('Frequently uses discounts', 'mailpoet'),
    category: SegmentTemplateCategories.SHOPPING_BEHAVIOR,
    description: __(
      'Customers who have regularly used coupons in the last 90 days.',
      'mailpoet',
    ),
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
