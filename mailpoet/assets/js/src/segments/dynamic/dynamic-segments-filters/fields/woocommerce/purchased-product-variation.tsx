import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { filter } from 'lodash/fp';
import { Select } from 'common/form/select/select';
import { MailPoet } from 'mailpoet';
import { ReactSelect } from 'common/form/react-select/react-select';
import { storeName } from '../../../store';
import {
  AnyValueTypes,
  FilterProps,
  SelectOption,
  Timeframe,
  WindowProducts,
  WooCommerceFormItem,
} from '../../../types';
import { DaysPeriodField, validateDaysPeriod } from '../days-period-field';

type VariationsResponse = {
  data: {
    product: { id: string; name: string } | null;
    variations: { id: string; name: string }[];
  };
};

export function validatePurchasedProductVariation(
  formItems: WooCommerceFormItem,
): boolean {
  return (
    Array.isArray(formItems.variation_ids) &&
    formItems.variation_ids.length > 0 &&
    !!formItems.operator &&
    validateDaysPeriod(formItems)
  );
}

export function PurchasedProductVariationFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter } = useDispatch(storeName);

  const variableProducts: WindowProducts = useSelect(
    (select) => select(storeName).getVariableProducts(),
    [],
  );

  const variableProductOptions = useMemo(
    () =>
      variableProducts.map((product) => ({
        value: product.id,
        label: product.name,
      })),
    [variableProducts],
  );

  const [parentProductId, setParentProductId] = useState<string | undefined>(
    undefined,
  );
  const [variations, setVariations] = useState<SelectOption[]>([]);
  const [isLoadingVariations, setIsLoadingVariations] =
    useState<boolean>(false);
  const [hasInitialized, setHasInitialized] = useState<boolean>(false);
  // Tracks the latest in-flight variations request so out-of-order
  // responses from rapid parent changes don't overwrite newer state.
  const variationsRequestIdRef = useRef(0);

  const loadVariationsByProduct = useCallback((productId: string) => {
    variationsRequestIdRef.current += 1;
    const requestId = variationsRequestIdRef.current;
    setIsLoadingVariations(true);
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'woocommerce_product_variations',
      action: 'getVariations',
      data: { product_id: productId },
    })
      .then((response: VariationsResponse) => {
        if (requestId !== variationsRequestIdRef.current) return;
        setVariations(
          response.data.variations.map((variation) => ({
            value: variation.id,
            label: variation.name,
          })),
        );
      })
      .always(() => {
        if (requestId !== variationsRequestIdRef.current) return;
        setIsLoadingVariations(false);
      });
  }, []);

  const loadVariationsByVariation = useCallback((variationId: string) => {
    variationsRequestIdRef.current += 1;
    const requestId = variationsRequestIdRef.current;
    setIsLoadingVariations(true);
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'woocommerce_product_variations',
      action: 'getVariations',
      data: { variation_id: variationId },
    })
      .then((response: VariationsResponse) => {
        if (requestId !== variationsRequestIdRef.current) return;
        if (response.data.product) {
          setParentProductId(response.data.product.id);
        }
        setVariations(
          response.data.variations.map((variation) => ({
            value: variation.id,
            label: variation.name,
          })),
        );
      })
      .always(() => {
        if (requestId !== variationsRequestIdRef.current) return;
        setIsLoadingVariations(false);
      });
  }, []);

  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment.operator, filterIndex]);

  useEffect(() => {
    if (hasInitialized) return;
    setHasInitialized(true);
    if (
      Array.isArray(segment.variation_ids) &&
      segment.variation_ids.length > 0
    ) {
      loadVariationsByVariation(segment.variation_ids[0]);
    }
  }, [hasInitialized, segment.variation_ids, loadVariationsByVariation]);

  const handleParentChange = (option: SelectOption | null): void => {
    const newParentId = option ? option.value : undefined;
    setParentProductId(newParentId);
    setVariations([]);
    void updateSegmentFilter({ variation_ids: [] }, filterIndex);
    if (newParentId) {
      loadVariationsByProduct(newParentId);
    }
  };

  const selectedVariations = filter((option) => {
    if (
      !Array.isArray(segment.variation_ids) ||
      segment.variation_ids.length === 0
    ) {
      return false;
    }
    return segment.variation_ids.indexOf(option.value) !== -1;
  }, variations);

  const selectedParent = parentProductId
    ? variableProductOptions.find((opt) => opt.value === parentProductId) ||
      null
    : null;

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
        dimension="small"
        isClearable
        key="select-variable-product"
        placeholder={MailPoet.I18n.t('selectWooVariableProduct')}
        options={variableProductOptions}
        value={selectedParent}
        onChange={(option: SelectOption | null): void =>
          handleParentChange(option)
        }
        automationId="select-variable-product"
      />
      <ReactSelect
        isMulti
        dimension="small"
        key="select-product-variations"
        placeholder={
          isLoadingVariations
            ? __('Loading variations…', 'mailpoet')
            : MailPoet.I18n.t('selectWooPurchasedProductVariation')
        }
        isDisabled={!parentProductId || isLoadingVariations}
        options={variations}
        value={selectedVariations}
        onChange={(options: SelectOption[]): void => {
          void updateSegmentFilter(
            {
              variation_ids: (options || []).map((x: SelectOption) => x.value),
            },
            filterIndex,
          );
        }}
        automationId="select-product-variations"
      />
      <DaysPeriodField
        filterIndex={filterIndex}
        defaultTimeframe={Timeframe.ALL_TIME}
      />
    </>
  );
}
