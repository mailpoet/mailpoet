import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { Grid } from 'common/grid';
import { Select } from 'common';
import { MailPoet } from 'mailpoet';
import { ReactSelect } from 'common/form/react_select/react_select';
import { filter } from 'lodash/fp';
import {
  WooCommerceFormItem,
  FilterProps,
  WooPaymentMethod,
  AnyValueTypes,
  SelectOption,
} from '../../../types';
import { storeName } from '../../../store';
import { DaysPeriodField, validateDaysPeriod } from '../days_period_field';

export function validateUsedPaymentMethod(
  formItems: WooCommerceFormItem,
): boolean {
  const usedPaymentMethodIsInvalid =
    !formItems.payment_methods ||
    formItems.payment_methods.length < 1 ||
    !formItems.operator ||
    !validateDaysPeriod(formItems);

  return !usedPaymentMethodIsInvalid;
}

export function UsedPaymentMethodFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter } = useDispatch(storeName);
  const paymentMethods: WooPaymentMethod[] = useSelect(
    (select) => select(storeName).getPaymentMethods(),
    [],
  );
  const paymentMethodOptions = paymentMethods.map((method) => ({
    value: method.id,
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
          key="select-operator-used-payment-methods"
          value={segment.operator}
          onChange={(e): void => {
            void updateSegmentFilter({ operator: e.target.value }, filterIndex);
          }}
          automationId="select-operator-used-payment-methods"
        >
          <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
          <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
          <option value={AnyValueTypes.NONE}>
            {MailPoet.I18n.t('noneOf')}
          </option>
        </Select>
        <ReactSelect
          key="select-payment-methods"
          isFullWidth
          isMulti
          placeholder={MailPoet.I18n.t('selectWooPaymentMethods')}
          options={paymentMethodOptions}
          value={filter((option) => {
            if (!segment.payment_methods) return undefined;
            return segment.payment_methods.indexOf(option.value) !== -1;
          }, paymentMethodOptions)}
          onChange={(options: SelectOption[]): void => {
            void updateSegmentFilter(
              {
                payment_methods: (options || []).map(
                  (x: SelectOption) => x.value,
                ),
              },
              filterIndex,
            );
          }}
          automationId="select-payment-methods"
        />
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <DaysPeriodField filterIndex={filterIndex} />
      </Grid.CenteredRow>
    </>
  );
}
