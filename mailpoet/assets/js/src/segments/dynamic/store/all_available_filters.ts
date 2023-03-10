import { __, _x } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';

import { GroupFilterValue } from '../types';
import { EmailSegmentOptions } from '../dynamic_segments_filters/email';
import { SubscriberSegmentOptions } from '../dynamic_segments_filters/subscriber';
import { WooCommerceOptions } from '../dynamic_segments_filters/woocommerce';
import { WooCommerceMembershipOptions } from '../dynamic_segments_filters/woocommerce_membership';
import { WooCommerceSubscriptionOptions } from '../dynamic_segments_filters/woocommerce_subscription';

export function getAvailableFilters(
  canUseWooSubscriptions: boolean,
  canUseWooMembership: boolean,
): GroupFilterValue[] {
  const filters: GroupFilterValue[] = [
    {
      label: __('Email', 'mailpoet'),
      options: EmailSegmentOptions,
    },
    {
      label: __('Subscriber', 'mailpoet'),
      options: SubscriberSegmentOptions,
    },
  ];
  if (MailPoet.isWoocommerceActive) {
    filters.push({
      label: _x(
        'WooCommerce',
        'Dynamic segment creation: User selects this to use any woocommerce filters',
        'mailpoet',
      ),
      options: WooCommerceOptions,
    });
  }
  if (MailPoet.isWoocommerceActive && canUseWooMembership) {
    filters.push({
      label: _x(
        'WooCommerce Memberships',
        'Dynamic segment creation: User selects this to use any WooCommerce Memberships filters',
        'mailpoet',
      ),
      options: WooCommerceMembershipOptions,
    });
  }
  if (MailPoet.isWoocommerceActive && canUseWooSubscriptions) {
    filters.push({
      label: _x(
        'WooCommerce Subscriptions',
        'Dynamic segment creation: User selects this to use any WooCommerce Subscriptions filters',
        'mailpoet',
      ),
      options: WooCommerceSubscriptionOptions,
    });
  }
  return filters;
}
