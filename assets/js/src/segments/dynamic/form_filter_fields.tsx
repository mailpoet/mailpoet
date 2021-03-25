import React from 'react';

import {
  AnyFormItem,
  FilterValue,
  OnFilterChange,
  SegmentTypes,
} from './types';

import { EmailFields } from './dynamic_segments_filters/email';
import { WordpressRoleFields } from './dynamic_segments_filters/wordpress_role';
import { WooCommerceFields } from './dynamic_segments_filters/woocommerce';

export interface FilterFieldsProps {
  segmentType: FilterValue;
  updateItem: OnFilterChange;
  item: AnyFormItem;
}

const filterFieldsMap = {
  [SegmentTypes.Email]: EmailFields,
  [SegmentTypes.WooCommerce]: WooCommerceFields,
  [SegmentTypes.WordPressRole]: WordpressRoleFields,
};

export const FormFilterFields: React.FunctionComponent<FilterFieldsProps> = ({
  segmentType,
  updateItem,
  item,
}) => {
  if (filterFieldsMap[segmentType.group] === undefined) return null;
  const Component = filterFieldsMap[segmentType.group];

  return (
    <Component
      onChange={updateItem}
      item={item}
    />
  );
};
