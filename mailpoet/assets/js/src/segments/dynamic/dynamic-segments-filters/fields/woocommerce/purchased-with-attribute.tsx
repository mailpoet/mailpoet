import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useMemo } from 'react';
import { Select } from 'common/form/select/select';
import { ReactSelect } from 'common/form/react-select/react-select';
import { filter } from 'lodash/fp';
import { storeName } from '../../../store';
import {
  AnyValueTypes,
  FilterProps,
  SelectOption,
  WindowLocalProductAttributes,
  WindowProductAttributes,
  WooCommerceFormItem,
} from '../../../types';

export function validatePurchasedWithAttribute(
  formItems: WooCommerceFormItem,
): boolean {
  const purchasedProductWithAttributeIsInvalid =
    !formItems.operator ||
    (formItems.attribute_type === 'taxonomy' &&
      (formItems.attribute_taxonomy_slug === undefined ||
        !Array.isArray(formItems.attribute_term_ids) ||
        formItems.attribute_term_ids.length === 0)) ||
    (formItems.attribute_type === 'local' &&
      (!formItems.attribute_local_name ||
        formItems.attribute_local_name.length === 0 ||
        !Array.isArray(formItems.attribute_local_values) ||
        formItems.attribute_local_values.length === 0));

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

  const productAttributesOptions = useMemo(
    () =>
      Object.values(productAttributes)
        .filter((attribute) => attribute.terms.length > 0)
        .map((attribute) => ({
          value: attribute.taxonomy,
          label: attribute.label,
        })),
    [productAttributes],
  );

  const localProductAttributes: WindowLocalProductAttributes = useSelect(
    (select) => select(storeName).getLocalProductAttributes(),
    [],
  );

  const localAttributeOptions = useMemo(
    () =>
      Object.values(localProductAttributes)
        .filter((attribute) => attribute.values.length > 0)
        .map((attribute) => ({
          // Appending @local to avoid conflicts between taxonomy and local attributes with the same name
          value: `${attribute.name}@local`,
          label: attribute.name,
        })),
    [localProductAttributes],
  );

  const localAttributeValues = useMemo(
    () => Object.values(localAttributeOptions).map((option) => option.value),
    [localAttributeOptions],
  );

  const combinedOptions = [
    ...productAttributesOptions,
    ...localAttributeOptions,
  ];

  const attributeValueOptions = useMemo(() => {
    if (segment.attribute_type === 'taxonomy') {
      return productAttributes[segment.attribute_taxonomy_slug].terms.map(
        (term) => ({
          value: term.term_id.toString(),
          label: term.name,
        }),
      );
    }
    if (segment.attribute_type === 'local') {
      return localProductAttributes[segment.attribute_local_name].values.map(
        (value) => ({
          value,
          label: value,
        }),
      );
    }
    return [];
  }, [
    segment.attribute_type,
    segment.attribute_taxonomy_slug,
    segment.attribute_local_name,
    productAttributes,
    localProductAttributes,
  ]);

  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  const attributeOnChange = useCallback(
    (option: SelectOption) => {
      if (localAttributeValues.includes(option.value)) {
        void updateSegmentFilter(
          {
            attribute_type: 'local',
            attribute_local_name: option.value.replace(/@local$/, ''),
            attribute_local_values: [],
            attribute_taxonomy_slug: null,
            attribute_term_ids: null,
          },
          filterIndex,
        );
      } else {
        void updateSegmentFilter(
          {
            attribute_type: 'taxonomy',
            attribute_local_name: null,
            attribute_local_values: null,
            attribute_taxonomy_slug: option.value,
            attribute_term_ids: [],
          },
          filterIndex,
        );
      }
    },
    [filterIndex, localAttributeValues, updateSegmentFilter],
  );

  const initialAttribute = useMemo(
    () =>
      segment.attribute_type === 'local'
        ? filter((localAttributeOption) => {
            if (!segment.attribute_local_name) {
              return undefined;
            }
            return (
              `${segment.attribute_local_name}@local` ===
              localAttributeOption.value
            );
          }, localAttributeOptions)
        : filter((productAttributeOption) => {
            if (segment.attribute_taxonomy_slug === undefined) {
              return undefined;
            }
            return (
              segment.attribute_taxonomy_slug === productAttributeOption.value
            );
          }, productAttributesOptions),
    [
      segment.attribute_type,
      segment.attribute_local_name,
      segment.attribute_taxonomy_slug,
      localAttributeOptions,
      productAttributesOptions,
    ],
  );

  const initialAttributeValues = useMemo(
    () =>
      filter((productAttributeTermOption: { value: string; label: string }) => {
        if (segment.attribute_local_values) {
          return (
            segment.attribute_local_values.indexOf(
              productAttributeTermOption.value,
            ) !== -1
          );
        }
        if (segment.attribute_term_ids) {
          return (
            segment.attribute_term_ids.indexOf(
              productAttributeTermOption.value,
            ) !== -1
          );
        }
        return undefined;
      }, attributeValueOptions),
    [
      segment.attribute_local_values,
      segment.attribute_term_ids,
      attributeValueOptions,
    ],
  );

  const attributeValuesOnChange = useCallback(
    (options: SelectOption[]): void => {
      if (segment.attribute_type === 'local') {
        void updateSegmentFilter(
          {
            attribute_term_ids: null,
            attribute_local_values: (options || []).map(
              (x: SelectOption) => x.value,
            ),
          },
          filterIndex,
        );
      } else {
        void updateSegmentFilter(
          {
            attribute_term_ids: (options || []).map(
              (x: SelectOption) => x.value,
            ),
            attribute_local_values: null,
          },
          filterIndex,
        );
      }
    },
    [segment.attribute_type, updateSegmentFilter, filterIndex],
  );

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
        options={combinedOptions}
        value={initialAttribute}
        onChange={attributeOnChange}
      />
      {attributeValueOptions.length > 0 && (
        <ReactSelect
          dimension="small"
          isMulti
          key="select-segment-product-attribute-terms"
          placeholder={__('Search attributes terms', 'mailpoet')}
          options={attributeValueOptions}
          value={initialAttributeValues}
          onChange={attributeValuesOnChange}
        />
      )}
    </>
  );
}
