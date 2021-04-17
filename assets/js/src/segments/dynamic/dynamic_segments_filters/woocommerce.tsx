import React from 'react';
import MailPoet from 'mailpoet';
import { assign, compose, find } from 'lodash/fp';
import Select from 'common/form/react_select/react_select';

import {
  OnFilterChange,
  SegmentTypes,
  SelectOption,
  WooCommerceFormItem,
} from '../types';
import { SegmentFormData } from '../segment_form_data';

export const WooCommerceOptions = [
  { value: 'purchasedCategory', label: MailPoet.I18n.t('wooPurchasedCategory'), group: SegmentTypes.WooCommerce },
  { value: 'purchasedProduct', label: MailPoet.I18n.t('wooPurchasedProduct'), group: SegmentTypes.WooCommerce },
];

enum WooCommerceActionTypes {
  PURCHASED_CATEGORY = 'purchasedCategory',
  PURCHASED_PRODUCT = 'purchasedProduct',
}

export function validateWooCommerce(formItems: WooCommerceFormItem): boolean {
  if (!(
    Object
      .values(WooCommerceActionTypes)
      .some((v) => v === formItems.action))
  ) {
    return false;
  }
  if (formItems.action === 'purchasedCategory' && !formItems.category_id) {
    return false;
  }
  if (formItems.action === 'purchasedProduct' && !formItems.product_id) {
    return false;
  }
  return true;
}

interface Props {
  onChange: OnFilterChange;
  item: WooCommerceFormItem;
}

export const WooCommerceFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
  const productOptions = SegmentFormData.products?.map((product) => ({
    value: product.id,
    label: product.name,
  }));

  const categoryOptions = SegmentFormData.productCategories?.map((product) => ({
    value: product.id,
    label: product.name,
  }));

  if (item.action === WooCommerceActionTypes.PURCHASED_PRODUCT) {
    return (
      <Select
        isFullWidth
        placeholder={MailPoet.I18n.t('selectWooPurchasedProduct')}
        options={productOptions}
        value={find(['value', item.product_id], productOptions)}
        onChange={(option: SelectOption): void => compose([
          onChange,
          assign(item),
        ])({ product_id: option.value })}
        automationId="select-segment-product"
      />
    );
  }

  return (
    <Select
      isFullWidth
      placeholder={MailPoet.I18n.t('selectWooPurchasedCategory')}
      options={categoryOptions}
      value={find(['value', item.category_id], categoryOptions)}
      onChange={(option: SelectOption): void => compose([
        onChange,
        assign(item),
      ])({ category_id: option.value })}
      automationId="select-segment-category"
    />
  );
};
