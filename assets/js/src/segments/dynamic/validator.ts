import { AnyFormItem, SegmentTypes } from './types';
import { validateEmail } from './dynamic_segments_filters/email';
import { validateWooCommerce } from './dynamic_segments_filters/woocommerce';
import { validateSubscriber } from './dynamic_segments_filters/subscriber';
import { validateWooCommerceSubscription } from './dynamic_segments_filters/woocommerce_subscription';

const validationMap = {
  [SegmentTypes.Email]: validateEmail,
  [SegmentTypes.WooCommerce]: validateWooCommerce,
  [SegmentTypes.WordPressRole]: validateSubscriber,
  [SegmentTypes.WooCommerceSubscription]: validateWooCommerceSubscription,
};

export function isFormValid(item: AnyFormItem): boolean {
  if (validationMap[item.segmentType] === undefined) return false;
  return validationMap[item.segmentType](item);
}
