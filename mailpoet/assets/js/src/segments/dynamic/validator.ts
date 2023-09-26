import { AnyFormItem, SegmentTypes } from './types';
import { validateAutomations } from './dynamic-segments-filters/automations';
import { validateEmail } from './dynamic-segments-filters/email';
import { validateWooCommerce } from './dynamic-segments-filters/woocommerce';
import { validateSubscriber } from './dynamic-segments-filters/subscriber';
import { validateWooCommerceMembership } from './dynamic-segments-filters/fields/woocommerce/woocommerce-membership';
import { validateWooCommerceSubscription } from './dynamic-segments-filters/fields/woocommerce/woocommerce-subscription';

const validationMap = {
  [SegmentTypes.Automations]: validateAutomations,
  [SegmentTypes.Email]: validateEmail,
  [SegmentTypes.WooCommerce]: validateWooCommerce,
  [SegmentTypes.WordPressRole]: validateSubscriber,
  [SegmentTypes.WooCommerceMembership]: validateWooCommerceMembership,
  [SegmentTypes.WooCommerceSubscription]: validateWooCommerceSubscription,
};

export function isFormValid(items: AnyFormItem[]): boolean {
  if (items.length < 1) return false;
  const validationResults: boolean[] = items.map((item: AnyFormItem) => {
    if (validationMap[item.segmentType] === undefined) return false;
    return validationMap[item.segmentType](item);
  });

  return validationResults.filter((result) => result === false).length === 0;
}
