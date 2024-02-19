import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { filter } from 'lodash/fp';
import { Select } from 'common/form/select/select';
import { MailPoet } from 'mailpoet';
import { ReactSelect } from 'common/form/react-select/react-select';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../../store';
import {
  AnyValueTypes,
  FilterProps,
  SelectOption,
  WindowProductCategories,
  WooCommerceFormItem,
} from '../../../types';

export function validatePurchasedTag(formItems: WooCommerceFormItem): boolean {
  const purchasedCategoryIsInvalid =
    formItems.tag_ids === undefined ||
    formItems.tag_ids.length === 0 ||
    !formItems.operator;

  return !purchasedCategoryIsInvalid;
}

export function PurchasedTagFields({ filterIndex }: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter } = useDispatch(storeName);

  const productTags: WindowProductCategories = useSelect(
    (select) => select(storeName).getProductTags(),
    [],
  );

  const tagOptions = productTags.map((product) => ({
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
      <Select
        key="select-operator"
        value={segment.operator}
        isMinWidth
        onChange={(e): void => {
          void updateSegmentFilter({ operator: e.target.value }, filterIndex);
        }}
        automationId="select-operator"
      >
        <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
        <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
        <option value={AnyValueTypes.NONE}>{MailPoet.I18n.t('noneOf')}</option>
      </Select>
      <ReactSelect
        isMulti
        dimension="small"
        key="select-segment-tag"
        placeholder={__('Search tags', 'mailpoet')}
        options={tagOptions}
        value={filter((tagOption) => {
          if (segment.tag_ids === undefined || segment.tag_ids.length === 0) {
            return undefined;
          }
          return segment.tag_ids.indexOf(tagOption.value) !== -1;
        }, tagOptions)}
        onChange={(options: SelectOption[]): void => {
          void updateSegmentFilter(
            {
              tag_ids: (options || []).map((x: SelectOption) => x.value),
            },
            filterIndex,
          );
        }}
        automationId="select-segment-tags"
      />
    </>
  );
}
