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

export const WooCommerceOptions = [
  { value: 'purchasedCategory', label: MailPoet.I18n.t('wooPurchasedCategory'), group: SegmentTypes.WooCommerce },
  { value: 'purchasedProduct', label: MailPoet.I18n.t('wooPurchasedProduct'), group: SegmentTypes.WooCommerce },
];

enum WooCommerceActionTypes {
  PURCHASED_CATEGORY = 'purchasedCategory',
  PURCHASED_PRODUCT = 'purchasedProduct',
}

export function validate(formItems: WooCommerceFormItem): boolean {
  if (!(formItems.action in WooCommerceActionTypes)) {
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

interface EmailWindow extends Window {
  mailpoet_products: {
    id: string;
    name: string;
  }[];
  mailpoet_product_categories: {
    id: string;
    name: string;
  }[];
}

declare let window: EmailWindow;

interface Props {
  onChange: OnFilterChange;
  item: WooCommerceFormItem;
}

export const WooCommerceFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
  const productOptions = window.mailpoet_products.map((product) => ({
    value: product.id,
    label: product.name,
  }));

  const categoryOptions = window.mailpoet_product_categories.map((product) => ({
    value: product.id,
    label: product.name,
  }));

  if (item.action === WooCommerceActionTypes.PURCHASED_PRODUCT) {
    return (
      <div className="mailpoet-form-field">
        <div className="mailpoet-form-input mailpoet-form-select">
          <Select
            placeholder={MailPoet.I18n.t('selectWooPurchasedProduct')}
            options={productOptions}
            value={find(['value', item.product_id], productOptions)}
            onChange={(option: SelectOption): void => compose([
              onChange,
              assign(item),
            ])({ product_id: option.value })}
          />
        </div>
      </div>
    );
  }

  return (
    <div className="mailpoet-form-field">
      <div className="mailpoet-form-input mailpoet-form-select">
        <Select
          placeholder={MailPoet.I18n.t('selectWooPurchasedCategory')}
          options={categoryOptions}
          value={find(['value', item.category_id], categoryOptions)}
          onChange={(option: SelectOption): void => compose([
            onChange,
            assign(item),
          ])({ category_id: option.value })}
        />
      </div>
    </div>
  );
};
