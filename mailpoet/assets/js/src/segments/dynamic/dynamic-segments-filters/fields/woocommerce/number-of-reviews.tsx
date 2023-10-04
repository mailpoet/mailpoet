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
  Timeframe,
  ReviewRating,
  CountType,
} from '../../../types';
import { validateDaysPeriod, DaysPeriodField } from '../days-period-field';
import { isInEnum } from '../../../../../utils';

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
    if (!isInEnum(segment.count_type, CountType)) {
      void updateSegmentFilter({ count_type: CountType.EQUALS }, filterIndex);
    }
    if (!isInEnum(segment.rating, ReviewRating)) {
      void updateSegmentFilter({ rating: ReviewRating.ANY }, filterIndex);
    }
    if (!isInEnum(segment.timeframe, Timeframe)) {
      void updateSegmentFilter(
        { timeframe: Timeframe.IN_THE_LAST },
        filterIndex,
      );
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      <Select
        key="rating-select"
        value={segment.rating}
        isMinWidth
        onChange={(e): void => {
          void updateSegmentFilterFromEvent('rating', filterIndex, e);
        }}
      >
        <option value={ReviewRating.ANY}>
          {MailPoet.I18n.t('wooAnyStarRating')}
        </option>
        <option value={ReviewRating.ONE}>
          {MailPoet.I18n.t('wooOneStarRating')}
        </option>
        <option value={ReviewRating.TWO}>
          {MailPoet.I18n.t('wooTwoStarRating')}
        </option>
        <option value={ReviewRating.THREE}>
          {MailPoet.I18n.t('wooThreeStarRating')}
        </option>
        <option value={ReviewRating.FOUR}>
          {MailPoet.I18n.t('wooFourStarRating')}
        </option>
        <option value={ReviewRating.FIVE}>
          {MailPoet.I18n.t('wooFiveStarRating')}
        </option>
      </Select>
      <Select
        key="select"
        value={segment.count_type}
        isMinWidth
        onChange={(e): void => {
          void updateSegmentFilterFromEvent('count_type', filterIndex, e);
        }}
        automationId="select-number-of-reviews-type"
      >
        <option value={CountType.EQUALS}>{MailPoet.I18n.t('equals')}</option>
        <option value={CountType.NOT_EQUALS}>
          {MailPoet.I18n.t('notEquals')}
        </option>
        <option value={CountType.MORE_THAN}>
          {MailPoet.I18n.t('moreThan')}
        </option>
        <option value={CountType.LESS_THAN}>
          {MailPoet.I18n.t('lessThan')}
        </option>
      </Select>
      <Input
        className="mailpoet-segments-input-small"
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
      <Grid.CenteredRow>
        <DaysPeriodField filterIndex={filterIndex} />
      </Grid.CenteredRow>
    </>
  );
}
