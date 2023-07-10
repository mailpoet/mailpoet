import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { Grid } from 'common/grid';
import { Input, Select } from 'common';
import { MailPoet } from 'mailpoet';
import { storeName } from '../../../store';
import { WooCommerceFormItem, FilterProps } from '../../../types';
import { DaysPeriodField, validateDaysPeriod } from '../days_period_field';

export function validateNumberOfOrders(
  formItems: WooCommerceFormItem,
): boolean {
  const numberOfOrdersIsInvalid =
    !formItems.number_of_orders_count ||
    !validateDaysPeriod(formItems) ||
    !formItems.number_of_orders_type;

  return !numberOfOrdersIsInvalid;
}

export function NumberOfOrdersFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);

  useEffect(() => {
    if (segment.number_of_orders_type === undefined) {
      void updateSegmentFilter({ number_of_orders_type: '=' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
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
        <DaysPeriodField filterIndex={filterIndex} />
      </Grid.CenteredRow>
    </>
  );
}
