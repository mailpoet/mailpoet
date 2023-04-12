import { FunctionComponent, useEffect } from 'react';
import { MailPoet } from 'mailpoet';
import { filter } from 'lodash/fp';
import { ReactSelect } from 'common/form/react_select/react_select';
import { Select } from 'common/form/select/select';
import { useDispatch, useSelect } from '@wordpress/data';

import { Grid } from 'common/grid';
import { Input } from 'common/form/input/input';
import {
  AnyValueTypes,
  SelectOption,
  WindowProductCategories,
  WindowProducts,
  WindowWooCommerceCountries,
  WooCommerceFormItem,
} from '../types';
import { DateFields, dateFieldValidator, DateOperator } from './date_fields';
import { storeName } from '../store';
import {
  actionTypesWithDefaultTypeAny,
  WooCommerceActionTypes,
} from './woocommerce_options';

export function validateWooCommerce(formItems: WooCommerceFormItem): boolean {
  if (
    !Object.values(WooCommerceActionTypes).some((v) => v === formItems.action)
  ) {
    return false;
  }
  const purchasedCategoryIsInvalid =
    formItems.category_ids === undefined ||
    formItems.category_ids.length === 0 ||
    !formItems.operator;
  if (
    formItems.action === WooCommerceActionTypes.PURCHASED_CATEGORY &&
    purchasedCategoryIsInvalid
  ) {
    return false;
  }
  const purchasedProductIsInvalid =
    formItems.product_ids === undefined ||
    formItems.product_ids.length === 0 ||
    !formItems.operator;
  if (
    formItems.action === WooCommerceActionTypes.PURCHASED_PRODUCT &&
    purchasedProductIsInvalid
  ) {
    return false;
  }
  const countryCodeIsInvalid =
    formItems.country_code === undefined || formItems.country_code.length === 0;
  if (
    formItems.action === WooCommerceActionTypes.CUSTOMER_IN_COUNTRY &&
    countryCodeIsInvalid
  ) {
    return false;
  }
  const numberOfOrdersIsInvalid =
    !formItems.number_of_orders_count ||
    !formItems.number_of_orders_days ||
    !formItems.number_of_orders_type;
  if (
    formItems.action === WooCommerceActionTypes.NUMBER_OF_ORDERS &&
    numberOfOrdersIsInvalid
  ) {
    return false;
  }
  if (
    formItems.action === WooCommerceActionTypes.TOTAL_SPENT &&
    (!formItems.total_spent_amount ||
      !formItems.total_spent_days ||
      !formItems.total_spent_type)
  ) {
    return false;
  }
  if (
    Object.values(DateOperator).includes(formItems.operator as DateOperator)
  ) {
    return dateFieldValidator(formItems);
  }
  if (
    formItems.action === WooCommerceActionTypes.SINGLE_ORDER_VALUE &&
    (!formItems.single_order_value_amount ||
      !formItems.single_order_value_days ||
      !formItems.single_order_value_type)
  ) {
    return false;
  }
  return true;
}

type Props = {
  filterIndex: number;
};

export const WooCommerceFields: FunctionComponent<Props> = ({
  filterIndex,
}) => {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);

  const productCategories: WindowProductCategories = useSelect(
    (select) => select(storeName).getProductCategories(),
    [],
  );
  const woocommerceCountries: WindowWooCommerceCountries = useSelect(
    (select) => select(storeName).getWooCommerceCountries(),
    [],
  );
  const products: WindowProducts = useSelect(
    (select) => select(storeName).getProducts(),
    [],
  );
  const wooCurrencySymbol: string = useSelect(
    (select) => select(storeName).getWooCommerceCurrencySymbol(),
    [],
  );
  const productOptions = products.map((product) => ({
    value: product.id,
    label: product.name,
  }));

  const categoryOptions = productCategories.map((product) => ({
    value: product.id,
    label: product.name,
  }));

  const countryOptions = woocommerceCountries.map((country) => ({
    value: country.code,
    label: country.name,
  }));

  const dateFields = DateFields({ filterIndex });

  let optionFields;

  useEffect(() => {
    if (
      segment.number_of_orders_type === undefined &&
      segment.action === WooCommerceActionTypes.NUMBER_OF_ORDERS
    ) {
      void updateSegmentFilter({ number_of_orders_type: '=' }, filterIndex);
    }
    if (
      segment.single_order_value_type === undefined &&
      segment.action === WooCommerceActionTypes.SINGLE_ORDER_VALUE
    ) {
      void updateSegmentFilter({ single_order_value_type: '>' }, filterIndex);
    }
    if (
      segment.total_spent_type === undefined &&
      segment.action === WooCommerceActionTypes.TOTAL_SPENT
    ) {
      void updateSegmentFilter({ total_spent_type: '>' }, filterIndex);
    }
    if (
      actionTypesWithDefaultTypeAny.includes(segment.action) &&
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
    if (
      segment.action === WooCommerceActionTypes.CUSTOMER_IN_COUNTRY &&
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  if (segment.action === WooCommerceActionTypes.PURCHASED_PRODUCT) {
    optionFields = (
      <>
        <Grid.CenteredRow>
          <Select
            key="select-operator"
            value={segment.operator}
            onChange={(e): void => {
              void updateSegmentFilter(
                { operator: e.target.value },
                filterIndex,
              );
            }}
            automationId="select-operator"
          >
            <option value={AnyValueTypes.ANY}>
              {MailPoet.I18n.t('anyOf')}
            </option>
            <option value={AnyValueTypes.ALL}>
              {MailPoet.I18n.t('allOf')}
            </option>
            <option value={AnyValueTypes.NONE}>
              {MailPoet.I18n.t('noneOf')}
            </option>
          </Select>
        </Grid.CenteredRow>
        <Grid.CenteredRow>
          <ReactSelect
            isMulti
            dimension="small"
            key="select-segment-products"
            isFullWidth
            placeholder={MailPoet.I18n.t('selectWooPurchasedProduct')}
            options={productOptions}
            value={filter((productOption) => {
              if (
                segment.product_ids === undefined ||
                segment.product_ids.length === 0
              ) {
                return undefined;
              }
              return segment.product_ids.indexOf(productOption.value) !== -1;
            }, productOptions)}
            onChange={(options: SelectOption[]): void => {
              void updateSegmentFilter(
                {
                  product_ids: (options || []).map(
                    (x: SelectOption) => x.value,
                  ),
                },
                filterIndex,
              );
            }}
            automationId="select-segment-products"
          />
        </Grid.CenteredRow>
      </>
    );
  } else if (segment.action === WooCommerceActionTypes.PURCHASED_CATEGORY) {
    optionFields = (
      <>
        <Grid.CenteredRow>
          <Select
            key="select-operator"
            value={segment.operator}
            onChange={(e): void => {
              void updateSegmentFilter(
                { operator: e.target.value },
                filterIndex,
              );
            }}
            automationId="select-operator"
          >
            <option value={AnyValueTypes.ANY}>
              {MailPoet.I18n.t('anyOf')}
            </option>
            <option value={AnyValueTypes.ALL}>
              {MailPoet.I18n.t('allOf')}
            </option>
            <option value={AnyValueTypes.NONE}>
              {MailPoet.I18n.t('noneOf')}
            </option>
          </Select>
        </Grid.CenteredRow>
        <Grid.CenteredRow>
          <ReactSelect
            isMulti
            dimension="small"
            key="select-segment-category"
            isFullWidth
            placeholder={MailPoet.I18n.t('selectWooPurchasedCategory')}
            options={categoryOptions}
            value={filter((categoryOption) => {
              if (
                segment.category_ids === undefined ||
                segment.category_ids.length === 0
              ) {
                return undefined;
              }
              return segment.category_ids.indexOf(categoryOption.value) !== -1;
            }, categoryOptions)}
            onChange={(options: SelectOption[]): void => {
              void updateSegmentFilter(
                {
                  category_ids: (options || []).map(
                    (x: SelectOption) => x.value,
                  ),
                },
                filterIndex,
              );
            }}
            automationId="select-segment-category"
          />
        </Grid.CenteredRow>
      </>
    );
  } else if (segment.action === WooCommerceActionTypes.PURCHASE_DATE) {
    optionFields = dateFields;
  } else if (segment.action === WooCommerceActionTypes.NUMBER_OF_ORDERS) {
    optionFields = (
      <>
        <Grid.CenteredRow>
          <Select
            key="select"
            value={segment.number_of_orders_type}
            onChange={(e): void => {
              void updateSegmentFilterFromEvent(
                'number_of_orders_type',
                filterIndex,
                e,
              );
            }}
            automationId="select-number-of-orders-type"
          >
            <option value="=">{MailPoet.I18n.t('equals')}</option>
            <option value="!=">{MailPoet.I18n.t('notEquals')}</option>
            <option value=">">{MailPoet.I18n.t('moreThan')}</option>
            <option value="<">{MailPoet.I18n.t('lessThan')}</option>
          </Select>
          <Input
            data-automation-id="input-number-of-orders-count"
            type="number"
            min={0}
            value={segment.number_of_orders_count || ''}
            placeholder={MailPoet.I18n.t('wooNumberOfOrdersCount')}
            onChange={(e): void => {
              void updateSegmentFilterFromEvent(
                'number_of_orders_count',
                filterIndex,
                e,
              );
            }}
          />
          <div>{MailPoet.I18n.t('wooNumberOfOrdersOrders')}</div>
        </Grid.CenteredRow>
        <Grid.CenteredRow>
          <div>{MailPoet.I18n.t('inTheLast')}</div>
          <Input
            data-automation-id="input-number-of-orders-days"
            type="number"
            min={1}
            value={segment.number_of_orders_days || ''}
            placeholder={MailPoet.I18n.t('daysPlaceholder')}
            onChange={(e): void => {
              void updateSegmentFilterFromEvent(
                'number_of_orders_days',
                filterIndex,
                e,
              );
            }}
          />
          <div>{MailPoet.I18n.t('days')}</div>
        </Grid.CenteredRow>
      </>
    );
  } else if (segment.action === WooCommerceActionTypes.TOTAL_SPENT) {
    optionFields = (
      <>
        <Grid.CenteredRow>
          <Select
            key="select"
            value={segment.total_spent_type}
            onChange={(e): void => {
              void updateSegmentFilterFromEvent(
                'total_spent_type',
                filterIndex,
                e,
              );
            }}
            automationId="select-total-spent-type"
          >
            <option value="=">{MailPoet.I18n.t('equals')}</option>
            <option value="!=">{MailPoet.I18n.t('notEquals')}</option>
            <option value=">">{MailPoet.I18n.t('moreThan')}</option>
            <option value="<">{MailPoet.I18n.t('lessThan')}</option>
          </Select>
          <Input
            data-automation-id="input-total-spent-amount"
            type="number"
            min={0}
            step={0.01}
            value={segment.total_spent_amount || ''}
            placeholder={MailPoet.I18n.t('wooSpentAmount')}
            onChange={(e): void => {
              void updateSegmentFilterFromEvent(
                'total_spent_amount',
                filterIndex,
                e,
              );
            }}
          />
          <div>{wooCurrencySymbol}</div>
        </Grid.CenteredRow>
        <Grid.CenteredRow>
          <div>{MailPoet.I18n.t('inTheLast')}</div>
          <Input
            data-automation-id="input-total-spent-days"
            type="number"
            min={1}
            value={segment.total_spent_days || ''}
            placeholder={MailPoet.I18n.t('daysPlaceholder')}
            onChange={(e): void => {
              void updateSegmentFilterFromEvent(
                'total_spent_days',
                filterIndex,
                e,
              );
            }}
          />
          <div>{MailPoet.I18n.t('days')}</div>
        </Grid.CenteredRow>
      </>
    );
  } else if (segment.action === WooCommerceActionTypes.SINGLE_ORDER_VALUE) {
    optionFields = (
      <>
        <Grid.CenteredRow>
          <Select
            key="select"
            value={segment.single_order_value_type}
            onChange={(e): void => {
              void updateSegmentFilterFromEvent(
                'single_order_value_type',
                filterIndex,
                e,
              );
            }}
            automationId="select-single-order-value-type"
          >
            <option value=">">{MailPoet.I18n.t('moreThan')}</option>
            <option value=">=">{MailPoet.I18n.t('moreThanOrEqual')}</option>
            <option value="=">{MailPoet.I18n.t('equals')}</option>
            <option value="!=">{MailPoet.I18n.t('notEquals')}</option>
            <option value="<">{MailPoet.I18n.t('lessThanOrEqual')}</option>
            <option value="<">{MailPoet.I18n.t('lessThan')}</option>
          </Select>
          <Input
            data-automation-id="input-single-order-value-amount"
            type="number"
            min={0}
            step={0.01}
            value={segment.single_order_value_amount || ''}
            placeholder={MailPoet.I18n.t('wooSpentAmount')}
            onChange={(e): void => {
              void updateSegmentFilterFromEvent(
                'single_order_value_amount',
                filterIndex,
                e,
              );
            }}
          />
          <div>{wooCurrencySymbol}</div>
        </Grid.CenteredRow>
        <Grid.CenteredRow>
          <div>{MailPoet.I18n.t('inTheLast')}</div>
          <Input
            data-automation-id="input-single-order-value-days"
            type="number"
            min={1}
            value={segment.single_order_value_days || ''}
            placeholder={MailPoet.I18n.t('daysPlaceholder')}
            onChange={(e): void => {
              void updateSegmentFilterFromEvent(
                'single_order_value_days',
                filterIndex,
                e,
              );
            }}
          />
          <div>{MailPoet.I18n.t('days')}</div>
        </Grid.CenteredRow>
      </>
    );
  } else if (segment.action === WooCommerceActionTypes.CUSTOMER_IN_COUNTRY) {
    optionFields = (
      <>
        <Grid.CenteredRow>
          <Select
            key="select-operator-country"
            value={segment.operator}
            onChange={(e): void => {
              void updateSegmentFilter(
                { operator: e.target.value },
                filterIndex,
              );
            }}
            automationId="select-operator-country"
          >
            <option value={AnyValueTypes.ANY}>
              {MailPoet.I18n.t('anyOf')}
            </option>
            <option value={AnyValueTypes.NONE}>
              {MailPoet.I18n.t('noneOf')}
            </option>
          </Select>
        </Grid.CenteredRow>
        <Grid.CenteredRow>
          <ReactSelect
            dimension="small"
            key="select-segment-country"
            isFullWidth
            isMulti
            placeholder={MailPoet.I18n.t('selectWooCountry')}
            options={countryOptions}
            value={filter((option) => {
              if (!segment.country_code) return undefined;
              return segment.country_code.indexOf(option.value) !== -1;
            }, countryOptions)}
            onChange={(options: SelectOption[]): void => {
              void updateSegmentFilter(
                {
                  country_code: (options || []).map(
                    (x: SelectOption) => x.value,
                  ),
                },
                filterIndex,
              );
            }}
            automationId="select-segment-country"
          />
        </Grid.CenteredRow>
      </>
    );
  }

  return optionFields;
};
