import { AnyFormItem, SegmentTypes } from './types';
import { validateEmail } from './dynamic_segments_filters/email';
import { validateWooCommerce } from './dynamic_segments_filters/woocommerce';
import { validateSubscriber } from './dynamic_segments_filters/subscriber';
import { validateWooCommerceMembership } from './dynamic_segments_filters/fields/woocommerce/woocommerce_membership';
import { validateWooCommerceSubscription } from './dynamic_segments_filters/fields/woocommerce/woocommerce_subscription';

const validationMap = {
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
