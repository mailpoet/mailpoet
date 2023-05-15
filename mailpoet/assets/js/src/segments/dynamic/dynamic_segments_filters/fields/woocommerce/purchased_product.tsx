import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { filter } from 'lodash/fp';
import { Grid } from 'common/grid';
import { Select } from 'common/form/select/select';
import { MailPoet } from 'mailpoet';
import { ReactSelect } from 'common/form/react_select/react_select';
import { storeName } from '../../../store';
import {
  AnyValueTypes,
  FilterProps,
  SelectOption,
  WindowProducts,
  WooCommerceFormItem,
} from '../../../types';

export function validatePurchasedProduct(
  formItems: WooCommerceFormItem,
): boolean {
  const purchasedProductIsInvalid =
    formItems.product_ids === undefined ||
    formItems.product_ids.length === 0 ||
    !formItems.operator;

  return !purchasedProductIsInvalid;
}

export function PurchasedProductFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter } = useDispatch(storeName);

  const products: WindowProducts = useSelect(
    (select) => select(storeName).getProducts(),
    [],
  );

  const productOptions = products.map((product) => ({
    value: product.id,
    label: product.name,
  }));

  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      <Grid.CenteredRow>
        <Select
          key="select-operator"
          value={segment.operator}
          onChange={(e): void => {
            void updateSegmentFilter({ operator: e.target.value }, filterIndex);
          }}
          automationId="select-operator"
        >
          <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
          <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
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
                product_ids: (options || []).map((x: SelectOption) => x.value),
              },
              filterIndex,
            );
          }}
          automationId="select-segment-products"
        />
      </Grid.CenteredRow>
    </>
  );
}
