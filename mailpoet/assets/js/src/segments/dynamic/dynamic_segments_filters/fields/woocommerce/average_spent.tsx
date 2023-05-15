import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { Grid } from 'common/grid';
import { Input, Select } from 'common';
import { MailPoet } from 'mailpoet';
import { storeName } from '../../../store';
import { WooCommerceFormItem, FilterProps } from '../../../types';

export function validateAverageSpent(formItems: WooCommerceFormItem): boolean {
  const averageSpentIsInvalid =
    !formItems.average_spent_amount ||
    !formItems.average_spent_type ||
    !formItems.average_spent_days;

  return !averageSpentIsInvalid;
}

export function AverageSpentFields({ filterIndex }: FilterProps): JSX.Element {
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
    const allowedOperators = ['>', '>=', '=', '!=', '<=', '<'];
    if (!allowedOperators.includes(segment.average_spent_type)) {
      void updateSegmentFilter({ average_spent_type: '>' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);
  return (
    <>
      <Grid.CenteredRow>
        <Select
          key="select"
          value={segment.average_spent_type}
          onChange={(e): void => {
            void updateSegmentFilterFromEvent(
              'average_spent_type',
              filterIndex,
              e,
            );
          }}
          automationId="select-average-spent-type"
        >
          <option value=">">{MailPoet.I18n.t('moreThan')}</option>
          <option value=">=">{MailPoet.I18n.t('moreThanOrEqual')}</option>
          <option value="=">{MailPoet.I18n.t('equals')}</option>
          <option value="!=">{MailPoet.I18n.t('notEquals')}</option>
          <option value="<=">{MailPoet.I18n.t('lessThanOrEqual')}</option>
          <option value="<">{MailPoet.I18n.t('lessThan')}</option>
        </Select>
        <Input
          data-automation-id="input-average-spent-amount"
          type="number"
          min={0}
          step={0.01}
          value={segment.average_spent_amount || ''}
          placeholder={MailPoet.I18n.t('wooSpentAmount')}
          onChange={(e): void => {
            void updateSegmentFilterFromEvent(
              'average_spent_amount',
              filterIndex,
              e,
            );
          }}
        />
        <div>{wooCurrencySymbol}</div>
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <div>{MailPoet.I18n.t('inTheLast')}</div>
        <Input
          data-automation-id="input-average-spent-days"
          type="number"
          min={1}
          step={1}
          value={segment.average_spent_days || ''}
          placeholder={MailPoet.I18n.t('daysPlaceholder')}
          onChange={(e): void => {
            void updateSegmentFilterFromEvent(
              'average_spent_days',
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
