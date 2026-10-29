import { MailPoet } from 'mailpoet';

import { GroupFilterValue } from '../types';
import { AutomationsOptions } from '../dynamic-segments-filters/automation-options';
import { getEmailSegmentOptions } from '../dynamic-segments-filters/email-options';
import { SubscriberSegmentOptions } from '../dynamic-segments-filters/subscriber-options';
import {
  getWooCommerceOptions,
  getWooCommerceMembershipOptions,
  getWooCommerceSubscriptionOptions,
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
      options: getEmailSegmentOptions(),
    },
    {
      label: MailPoet.I18n.t('wpUserRole'),
      options: SubscriberSegmentOptions,
    },
  ];
  if (MailPoet.isWoocommerceActive) {
    filters.push({
      label: MailPoet.I18n.t('woocommerce'),
      options: getWooCommerceOptions(),
    });
  }
  if (MailPoet.isWoocommerceActive && canUseWooMembership) {
    filters.push({
      label: MailPoet.I18n.t('woocommerceMemberships'),
      options: getWooCommerceMembershipOptions(),
    });
  }
  if (MailPoet.isWoocommerceActive && canUseWooSubscriptions) {
    filters.push({
      label: MailPoet.I18n.t('woocommerceSubscriptions'),
      options: getWooCommerceSubscriptionOptions(),
    });
  }
  return filters;
}
