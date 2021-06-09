import React from 'react';
import { useSelect } from '@wordpress/data';

import {
  SegmentTypes,
  WordpressRoleFormItem,
} from './types';

import { EmailFields } from './dynamic_segments_filters/email';
import { SubscriberFields } from './dynamic_segments_filters/subscriber';
import { WooCommerceFields } from './dynamic_segments_filters/woocommerce';
import { WooCommerceSubscriptionFields } from './dynamic_segments_filters/woocommerce_subscription';

const filterFieldsMap = {
  [SegmentTypes.Email]: EmailFields,
  [SegmentTypes.WooCommerce]: WooCommerceFields,
  [SegmentTypes.WordPressRole]: SubscriberFields,
  [SegmentTypes.WooCommerceSubscription]: WooCommerceSubscriptionFields,
};

type Props = {
  filterIndex: number;
}

export const FormFilterFields: React.FunctionComponent<Props> = ({ filterIndex }) => {
  const filter: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex]
  );

  if (filter === undefined || filterFieldsMap[filter.segmentType] === undefined) return null;
  const Component = filterFieldsMap[filter.segmentType];

  return (
    <Component filterIndex={filterIndex} />
  );
};
