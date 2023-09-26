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
  WindowProductCategories,
  WooCommerceFormItem,
} from '../../../types';

export function validatePurchasedCategory(
  formItems: WooCommerceFormItem,
): boolean {
  const purchasedCategoryIsInvalid =
    formItems.category_ids === undefined ||
    formItems.category_ids.length === 0 ||
    !formItems.operator;

  return !purchasedCategoryIsInvalid;
}

export function PurchasedCategoryFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter } = useDispatch(storeName);

  const productCategories: WindowProductCategories = useSelect(
    (select) => select(storeName).getProductCategories(),
    [],
  );

  const categoryOptions = productCategories.map((product) => ({
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
                category_ids: (options || []).map((x: SelectOption) => x.value),
              },
              filterIndex,
            );
          }}
          automationId="select-segment-category"
        />
      </Grid.CenteredRow>
    </>
  );
}
