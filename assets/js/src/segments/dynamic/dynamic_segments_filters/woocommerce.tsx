import React, { useEffect } from 'react';
import MailPoet from 'mailpoet';
import { assign, compose, find } from 'lodash/fp';
import Select from 'common/form/react_select/react_select';

import { Grid } from 'common/grid';
import Input from 'common/form/input/input';
import {
  OnFilterChange,
  SegmentTypes,
  SelectOption,
  WooCommerceFormItem,
} from '../types';
import { SegmentFormData } from '../segment_form_data';

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
    { value: '=', label: MailPoet.I18n.t('wooNumberOfOrdersEqual') },
    { value: '>', label: MailPoet.I18n.t('wooNumberOfOrdersMoreThan') },
    { value: '<', label: MailPoet.I18n.t('wooNumberOfOrdersLessThan') },
  ];

  let optionFields;

  useEffect(() => {
    if (
      item.number_of_orders_type === undefined
      && item.action === WooCommerceActionTypes.NUMBER_OF_ORDERS
    ) {
      onChange(assign(item, { number_of_orders_type: '=' }));
    }
  }, [onChange, item]);

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
    optionFields = (
      <div>
        <Grid.CenteredRow className="mailpoet-form-field">
          <div data-automation-id="select-number-of-orders-type">
            <Select
              options={numberOfOrdersTypeOptions}
              value={find(['value', item.number_of_orders_type], numberOfOrdersTypeOptions)}
              onChange={(option: SelectOption): void => compose([
                onChange,
                assign(item),
              ])({ number_of_orders_type: option.value })}
            />
          </div>
          <Input
            data-automation-id="input-number-of-orders-count"
            type="number"
            min={0}
            value={item.number_of_orders_count || ''}
            placeholder={MailPoet.I18n.t('wooNumberOfOrdersCount')}
            onChange={(event): void => compose([
              onChange,
              assign(item),
            ])({ number_of_orders_count: event.target.value })}
          />
          <div>{MailPoet.I18n.t('wooNumberOfOrdersOrders')}</div>
        </Grid.CenteredRow>
        <Grid.CenteredRow className="mailpoet-form-field">
          <div>{MailPoet.I18n.t('wooNumberOfOrdersInTheLast')}</div>
          <Input
            data-automation-id="input-number-of-orders-days"
            type="number"
            min={1}
            value={item.number_of_orders_days || ''}
            placeholder={MailPoet.I18n.t('wooNumberOfOrdersDaysPlaceholder')}
            onChange={(event): void => compose([
              onChange,
              assign(item),
            ])({ number_of_orders_days: event.target.value })}
          />
          <div>{MailPoet.I18n.t('wooNumberOfOrdersDays')}</div>
        </Grid.CenteredRow>
      </div>
    );
  }

  return optionFields;
};
