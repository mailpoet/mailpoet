import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { Grid } from 'common/grid';
import { Input, Select } from 'common';
import { MailPoet } from 'mailpoet';
import { storeName } from '../../../store';
import {
  WooCommerceFormItem,
  FilterProps,
  DaysPeriodItem,
} from '../../../types';
import { validateDaysPeriod, DaysPeriodField } from '../days_period_field';

export function validateNumberOfReviews(
  formItems: WooCommerceFormItem,
): boolean {
  const numberOfOrdersIsInvalid =
    !formItems.count ||
    !formItems.count_type ||
    !formItems.rating ||
    !validateDaysPeriod(formItems);

  return !numberOfOrdersIsInvalid;
}

export function NumberOfReviewsFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem & DaysPeriodItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);

  useEffect(() => {
    if (segment.count_type === undefined) {
      void updateSegmentFilter({ count_type: '=' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  if (!['inTheLast', 'allTime'].includes(segment.timeframe)) {
    void updateSegmentFilter({ timeframe: 'inTheLast' }, filterIndex);
  }

  return (
    <>
      <Grid.CenteredRow>
        <Select
          key="rating-select"
          value={segment.rating}
          onChange={(e): void => {
            void updateSegmentFilterFromEvent('rating', filterIndex, e);
          }}
        >
          <option value="any">{MailPoet.I18n.t('wooAnyStarRating')}</option>
          <option value="1">{MailPoet.I18n.t('wooOneStarRating')}</option>
          <option value="2">{MailPoet.I18n.t('wooTwoStarRating')}</option>
          <option value="3">{MailPoet.I18n.t('wooThreeStarRating')}</option>
          <option value="4">{MailPoet.I18n.t('wooFourStarRating')}</option>
          <option value="5">{MailPoet.I18n.t('wooFiveStarRating')}</option>
        </Select>
        <Select
          key="select"
          value={segment.count_type}
          onChange={(e): void => {
            void updateSegmentFilterFromEvent('count_type', filterIndex, e);
          }}
          automationId="select-number-of-reviews-type"
        >
          <option value="=">{MailPoet.I18n.t('equals')}</option>
          <option value="!=">{MailPoet.I18n.t('notEquals')}</option>
          <option value=">">{MailPoet.I18n.t('moreThan')}</option>
          <option value="<">{MailPoet.I18n.t('lessThan')}</option>
        </Select>
        <Input
          data-automation-id="input-number-of-reviews-count"
          type="number"
          min={0}
          value={segment.count || ''}
          placeholder={MailPoet.I18n.t('wooNumberOfOrdersCount')}
          onChange={(e): void => {
            void updateSegmentFilterFromEvent('count', filterIndex, e);
          }}
        />
        <div>{MailPoet.I18n.t('wooNumberOfReviewsReviews')}</div>
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <DaysPeriodField filterIndex={filterIndex} />
      </Grid.CenteredRow>
    </>
  );
}
