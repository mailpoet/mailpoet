import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from 'react';
import { Select } from 'common/form/select/select';
import { ReactSelect } from 'common/form/react-select/react-select';
import { filter } from 'lodash/fp';
import { storeName } from '../../../store';
import {
  AnyValueTypes,
  FilterProps,
  SelectOption,
  WindowProductAttributes,
  WooCommerceFormItem,
} from '../../../types';

export function validatePurchasedWithAttribute(
  formItems: WooCommerceFormItem,
): boolean {
  const purchasedProductWithAttributeIsInvalid =
    !formItems.operator ||
    formItems.attribute_id === undefined ||
    formItems.attribute_term_id === undefined;

  return !purchasedProductWithAttributeIsInvalid;
}

export function PurchasedWithAttributeFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter } = useDispatch(storeName);

  const productAttributes: WindowProductAttributes = useSelect(
    (select) => select(storeName).getProductAttributes(),
    [],
  );

  const productAttributesOptions = Object.values(productAttributes).map(
    (attribute) => ({
      value: attribute.id,
      label: attribute.label,
    }),
  );

  const productAttributeTermsOptionsRef = useRef(null);

  useEffect(() => {
    if (segment.attribute_id === undefined) {
      productAttributeTermsOptionsRef.current = null;
      return;
    }

    productAttributeTermsOptionsRef.current = productAttributes[
      segment.attribute_id
    ].terms.map((term) => ({
      value: term.term_id,
      label: term.name,
    }));
  }, [segment.attribute_id, productAttributes]);

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
      >
        <option value={AnyValueTypes.ANY}>{__('any of', 'mailpoet')}</option>
        <option value={AnyValueTypes.ALL}>{__('all of', 'mailpoet')}</option>
        <option value={AnyValueTypes.NONE}>{__('none of', 'mailpoet')}</option>
      </Select>
      <ReactSelect
        dimension="small"
        key="select-segment-product-attribute"
        placeholder={__('Search attributes', 'mailpoet')}
        options={productAttributesOptions}
        value={filter((productAttributeOption) => {
          if (segment.attribute_id === undefined) {
            return undefined;
          }
          return segment.attribute_id === productAttributeOption.value;
        }, productAttributesOptions)}
        onChange={(option: SelectOption): void => {
          void updateSegmentFilter(
            {
              attribute_id: option.value,
            },
            filterIndex,
          );
        }}
      />
      {productAttributeTermsOptionsRef.current && (
        <ReactSelect
          dimension="small"
          key="select-segment-product-attribute-terms"
          placeholder={__('Search attributes terms', 'mailpoet')}
          options={productAttributeTermsOptionsRef.current}
          value={filter(
            (productAttributeTermOption: { value: string; label: string }) => {
              if (segment.attribute_term_id === undefined) {
                return undefined;
              }
              return (
                segment.attribute_term_id === productAttributeTermOption.value
              );
            },
            productAttributeTermsOptionsRef.current,
          )}
          onChange={(option: SelectOption): void => {
            void updateSegmentFilter(
              {
                attribute_term_id: option.value,
              },
              filterIndex,
            );
          }}
        />
      )}
    </>
  );
}
