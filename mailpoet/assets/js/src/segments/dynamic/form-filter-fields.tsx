import { useSelect } from '@wordpress/data';

import { FilterProps, SegmentTypes, WordpressRoleFormItem } from './types';

import { EmailFields } from './dynamic-segments-filters/email';
import { SubscriberFields } from './dynamic-segments-filters/subscriber';
import { WooCommerceFields } from './dynamic-segments-filters/woocommerce';
import { AutomationsFields } from './dynamic-segments-filters/automations';
import { WooCommerceMembershipFields } from './dynamic-segments-filters/fields/woocommerce/woocommerce-membership';
import { WooCommerceSubscriptionFields } from './dynamic-segments-filters/fields/woocommerce/woocommerce-subscription';
import { storeName } from './store';

const filterFieldsMap = {
  [SegmentTypes.Automations]: AutomationsFields,
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
