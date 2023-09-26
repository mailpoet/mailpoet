import { MailPoet } from 'mailpoet';

import { GroupFilterValue } from '../types';
import { AutomationsOptions } from '../dynamic-segments-filters/automation-options';
import { EmailSegmentOptions } from '../dynamic-segments-filters/email-options';
import { SubscriberSegmentOptions } from '../dynamic-segments-filters/subscriber-options';
import {
  WooCommerceOptions,
  WooCommerceMembershipOptions,
  WooCommerceSubscriptionOptions,
} from '../dynamic-segments-filters/woocommerce-options';

export function getAvailableFilters(
  canUseWooSubscriptions: boolean,
  canUseWooMembership: boolean,
): GroupFilterValue[] {
  const filters: GroupFilterValue[] = [
    {
      label: MailPoet.I18n.t('automations'),
      options: AutomationsOptions,
    },
    {
      label: MailPoet.I18n.t('email'),
      options: EmailSegmentOptions,
    },
    {
      label: MailPoet.I18n.t('wpUserRole'),
      options: SubscriberSegmentOptions,
    },
  ];
  if (MailPoet.isWoocommerceActive) {
    filters.push({
      label: MailPoet.I18n.t('woocommerce'),
      options: WooCommerceOptions,
    });
  }
  if (MailPoet.isWoocommerceActive && canUseWooMembership) {
    filters.push({
      label: MailPoet.I18n.t('woocommerceMemberships'),
      options: WooCommerceMembershipOptions,
    });
  }
  if (MailPoet.isWoocommerceActive && canUseWooSubscriptions) {
    filters.push({
      label: MailPoet.I18n.t('woocommerceSubscriptions'),
      options: WooCommerceSubscriptionOptions,
    });
  }
  return filters;
}
