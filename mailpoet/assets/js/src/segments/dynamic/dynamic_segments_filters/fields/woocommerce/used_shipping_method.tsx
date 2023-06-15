import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { Grid } from 'common/grid';
import { Input, Select } from 'common';
import { MailPoet } from 'mailpoet';
import { ReactSelect } from 'common/form/react_select/react_select';
import { filter } from 'lodash/fp';
import {
  WooCommerceFormItem,
  FilterProps,
  WooShippingMethod,
  AnyValueTypes,
  SelectOption,
} from '../../../types';
import { storeName } from '../../../store';

export function validateUsedShippingMethod(
  formItems: WooCommerceFormItem,
): boolean {
  const usedShippingMethodIsInvalid =
    !formItems.shipping_methods ||
    formItems.shipping_methods.length < 1 ||
    !formItems.operator ||
    !formItems.used_shipping_method_days ||
    parseInt(formItems.used_shipping_method_days, 10) < 1;

  return !usedShippingMethodIsInvalid;
}

export function UsedShippingMethodFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);
  const shippingMethods: WooShippingMethod[] = useSelect(
    (select) => select(storeName).getShippingMethods(),
    [],
  );
  const shippingMethodOptions = shippingMethods.map((method) => ({
    value: method.instanceId,
    label: method.name,
  }));

  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      <Grid.CenteredRow>
        <Select
          isMaxContentWidth
          key="select-operator-used-shipping-methods"
          value={segment.operator}
          onChange={(e): void => {
            void updateSegmentFilter({ operator: e.target.value }, filterIndex);
          }}
          automationId="select-operator-used-shipping-methods"
        >
          <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
          <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
          <option value={AnyValueTypes.NONE}>
            {MailPoet.I18n.t('noneOf')}
          </option>
        </Select>
        <ReactSelect
          key="select-shipping-methods"
          isFullWidth
          isMulti
          placeholder={MailPoet.I18n.t('selectWooShippingMethods')}
          options={shippingMethodOptions}
          value={filter((option) => {
            if (!segment.shipping_methods) return undefined;
            return segment.shipping_methods.indexOf(option.value) !== -1;
          }, shippingMethodOptions)}
          onChange={(options: SelectOption[]): void => {
            void updateSegmentFilter(
              {
                shipping_methods: (options || []).map(
                  (x: SelectOption) => x.value,
                ),
              },
              filterIndex,
            );
          }}
          automationId="select-shipping-methods"
        />
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <div>{MailPoet.I18n.t('inTheLast')}</div>
        <Input
          data-automation-id="input-used-shipping-days"
          type="number"
          min={1}
          value={segment.used_shipping_method_days || ''}
          placeholder={MailPoet.I18n.t('daysPlaceholder')}
          onChange={(e): void => {
            void updateSegmentFilterFromEvent(
              'used_shipping_method_days',
              filterIndex,
              e,
            );
          }}
        />
        <div>{MailPoet.I18n.t('days')}</div>
      </Grid.CenteredRow>
    </>
  );
}
