import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { Grid } from 'common/grid';
import { Input, Select } from 'common';
import { MailPoet } from 'mailpoet';
import { WooCommerceFormItem, FilterProps } from '../../../types';
import { storeName } from '../../../store';
import { DaysPeriodField, validateDaysPeriod } from '../days_period_field';

export function validateSingleOrderValue(
  formItems: WooCommerceFormItem,
): boolean {
  const singleOrderValueIsInvalid =
    !formItems.single_order_value_amount ||
    !validateDaysPeriod(formItems) ||
    !formItems.single_order_value_type;

  return !singleOrderValueIsInvalid;
}

export function SingleOrderValueFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);
  const wooCurrencySymbol: string = useSelect(
    (select) => select(storeName).getWooCommerceCurrencySymbol(),
    [],
  );
  useEffect(() => {
    if (segment.single_order_value_type === undefined) {
      void updateSegmentFilter({ single_order_value_type: '>' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);
  return (
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
          <option value="<=">{MailPoet.I18n.t('lessThanOrEqual')}</option>
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
        <DaysPeriodField filterIndex={filterIndex} />
      </Grid.CenteredRow>
    </>
  );
}
