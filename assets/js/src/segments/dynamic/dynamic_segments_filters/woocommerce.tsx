import React, { useEffect } from 'react';
import MailPoet from 'mailpoet';
import { assign, compose, find } from 'lodash/fp';
import ReactSelect from 'common/form/react_select/react_select';
import Select from 'common/form/select/select';

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
  { value: 'totalSpent', label: MailPoet.I18n.t('wooTotalSpent'), group: SegmentTypes.WooCommerce },
];

enum WooCommerceActionTypes {
  NUMBER_OF_ORDERS = 'numberOfOrders',
  PURCHASED_CATEGORY = 'purchasedCategory',
  PURCHASED_PRODUCT = 'purchasedProduct',
  TOTAL_SPENT = 'totalSpent',
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
  if (formItems.action === 'totalSpent' && (!formItems.total_spent_amount || !formItems.total_spent_days || !formItems.total_spent_type)) {
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

  let optionFields;

  useEffect(() => {
    if (
      item.number_of_orders_type === undefined
      && item.action === WooCommerceActionTypes.NUMBER_OF_ORDERS
    ) {
      onChange(assign(item, { number_of_orders_type: '=' }));
    }
    if (
      item.total_spent_type === undefined
      && item.action === WooCommerceActionTypes.TOTAL_SPENT
    ) {
      onChange(assign(item, { total_spent_type: '>' }));
    }
  }, [onChange, item]);

  if (item.action === WooCommerceActionTypes.PURCHASED_PRODUCT) {
    optionFields = (
      <ReactSelect
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
      <ReactSelect
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
        <div className="mailpoet-gap" />
        <Grid.CenteredRow className="mailpoet-form-field">
          <Select
            key="select"
            value={item.number_of_orders_type}
            onChange={(e): void => compose([
              onChange,
              assign(item),
            ])({ number_of_orders_type: e.target.value })}
            automationId="select-number-of-orders-type"
          >
            <option value="=">{MailPoet.I18n.t('wooNumberOfOrdersEqual')}</option>
            <option value=">">{MailPoet.I18n.t('wooNumberOfOrdersMoreThan')}</option>
            <option value="<">{MailPoet.I18n.t('wooNumberOfOrdersLessThan')}</option>
          </Select>
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
        <div className="mailpoet-gap" />
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
  } else if (item.action === WooCommerceActionTypes.TOTAL_SPENT) {
    optionFields = (
      <div>
        <div className="mailpoet-gap" />
        <Grid.CenteredRow className="mailpoet-form-field">
          <Select
            key="select"
            value={item.total_spent_type}
            onChange={(e): void => compose([
              onChange,
              assign(item),
            ])({ total_spent_type: e.target.value })}
            automationId="select-total-spent-type"
          >
            <option value=">">{MailPoet.I18n.t('wooTotalSpentMoreThan')}</option>
            <option value="<">{MailPoet.I18n.t('wooTotalSpentLessThan')}</option>
          </Select>
          <Input
            data-automation-id="input-total-spent-amount"
            type="number"
            min={0}
            step={0.01}
            value={item.total_spent_amount || ''}
            placeholder={MailPoet.I18n.t('wooTotalSpentAmount')}
            onChange={(event): void => compose([
              onChange,
              assign(item),
            ])({ total_spent_amount: event.target.value })}
          />
          <div>{SegmentFormData.wooCurrencySymbol}</div>
        </Grid.CenteredRow>
        <div className="mailpoet-gap" />
        <Grid.CenteredRow className="mailpoet-form-field">
          <div>{MailPoet.I18n.t('wooTotalSpentInTheLast')}</div>
          <Input
            data-automation-id="input-total-spent-days"
            type="number"
            min={1}
            value={item.total_spent_days || ''}
            placeholder={MailPoet.I18n.t('wooTotalSpentDaysPlaceholder')}
            onChange={(event): void => compose([
              onChange,
              assign(item),
            ])({ total_spent_days: event.target.value })}
          />
          <div>{MailPoet.I18n.t('wooTotalSpentDays')}</div>
        </Grid.CenteredRow>
      </div>
    );
  }

  return optionFields;
};
