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

export const FormFilterFields: React.FunctionComponent = () => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  if (filterFieldsMap[segment.segmentType] === undefined) return null;
  const Component = filterFieldsMap[segment.segmentType];

  return (
    <Component />
  );
};
