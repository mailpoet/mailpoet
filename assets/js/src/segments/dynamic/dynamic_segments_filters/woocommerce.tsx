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
import { Grid } from '../../../common/grid';
import Input from '../../../common/form/input/input';

export const WooCommerceOptions = [
  { value: 'numberOfOrders', label: MailPoet.I18n.t('wooNumberOfOrders'), group: SegmentTypes.WooCommerce },
  { value: 'purchasedCategory', label: MailPoet.I18n.t('wooPurchasedCategory'), group: SegmentTypes.WooCommerce },
  { value: 'purchasedProduct', label: MailPoet.I18n.t('wooPurchasedProduct'), group: SegmentTypes.WooCommerce },
];

enum WooCommerceActionTypes {
  NUMBER_OF_ORDERS = 'numberOfOrders',
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
  if (formItems.action === 'numberOfOrders' && (!formItems.number_of_orders_count || !formItems.number_of_orders_days || !formItems.number_of_orders_type)) {
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

  const numberOfOrdersTypeOptions = [
    { value: '=', label: 'equals' },
    { value: '>', label: 'more than' },
    { value: '<', label: 'less than' },
  ];

  let optionFields;

  if (item.action === WooCommerceActionTypes.PURCHASED_PRODUCT) {
    optionFields = (
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
  } else if (item.action === WooCommerceActionTypes.PURCHASED_CATEGORY) {
    optionFields = (
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
  } else if (item.action === WooCommerceActionTypes.NUMBER_OF_ORDERS) {
    if (!item.number_of_orders_type) {
      item.number_of_orders_type = '=';
    }

    optionFields = (
      <div>
        <Grid.CenteredRow className="mailpoet-form-field">
          <Select
            options={numberOfOrdersTypeOptions}
            value={find(['value', item.number_of_orders_type], numberOfOrdersTypeOptions)}
            onChange={(option: SelectOption): void => compose([
              onChange,
              assign(item),
            ])({ number_of_orders_type: option.value })}
          />
          <Input
            type="number"
            min={0}
            value={item.number_of_orders_count || ''}
            placeholder="count"
            onChange={(event): void => compose([
              onChange,
              assign(item),
            ])({ number_of_orders_count: event.target.value })}
          />
          <div>orders</div>
        </Grid.CenteredRow>
        <Grid.CenteredRow className="mailpoet-form-field">
          <div>in the last</div>
          <Input
            type="number"
            min={1}
            value={item.number_of_orders_days || ''}
            placeholder="days"
            onChange={(event): void => compose([
              onChange,
              assign(item),
            ])({ number_of_orders_days: event.target.value })}
          />
          <div>days</div>
        </Grid.CenteredRow>
      </div>
    );
  }

  return optionFields;
};
