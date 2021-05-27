import MailPoet from 'mailpoet';

import { GroupFilterValue } from './types';
import { EmailSegmentOptions } from './dynamic_segments_filters/email';
import { SubscriberSegmentOptions } from './dynamic_segments_filters/subscriber';
import { WooCommerceOptions } from './dynamic_segments_filters/woocommerce';
import { WooCommerceSubscriptionOptions } from './dynamic_segments_filters/woocommerce_subscription';

export function getAvailableFilters(canUseWooSubscriptions: boolean): GroupFilterValue[] {
  const filters: GroupFilterValue[] = [
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
  if (MailPoet.isWoocommerceActive && canUseWooSubscriptions) {
    filters.push({
      label: MailPoet.I18n.t('woocommerceSubscriptions'),
      options: WooCommerceSubscriptionOptions,
    });
  }
  return filters;
}
