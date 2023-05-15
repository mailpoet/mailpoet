import { useSelect } from '@wordpress/data';

import { FilterProps, SegmentTypes, WordpressRoleFormItem } from './types';

import { EmailFields } from './dynamic_segments_filters/email';
import { SubscriberFields } from './dynamic_segments_filters/subscriber';
import { WooCommerceFields } from './dynamic_segments_filters/woocommerce';
import { WooCommerceMembershipFields } from './dynamic_segments_filters/fields/woocommerce/woocommerce_membership';
import { WooCommerceSubscriptionFields } from './dynamic_segments_filters/fields/woocommerce/woocommerce_subscription';
import { storeName } from './store';

const filterFieldsMap = {
  [SegmentTypes.Email]: EmailFields,
  [SegmentTypes.WooCommerce]: WooCommerceFields,
  [SegmentTypes.WordPressRole]: SubscriberFields,
  [SegmentTypes.WooCommerceMembership]: WooCommerceMembershipFields,
  [SegmentTypes.WooCommerceSubscription]: WooCommerceSubscriptionFields,
};

export function FormFilterFields({ filterIndex }: FilterProps): JSX.Element {
  const filter: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  if (filter === undefined || filterFieldsMap[filter.segmentType] === undefined)
    return null;
  const Component = filterFieldsMap[filter.segmentType];

  return <Component filterIndex={filterIndex} />;
}
