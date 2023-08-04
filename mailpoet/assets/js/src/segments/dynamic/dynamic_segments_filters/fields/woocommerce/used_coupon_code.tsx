import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { Grid } from 'common/grid';
import { Select } from 'common';
import { ReactSelect } from 'common/form/react_select/react_select';
import { MailPoet } from 'mailpoet';
import { filter } from 'lodash/fp';
import {
  WooCommerceFormItem,
  FilterProps,
  AnyValueTypes,
  Coupon,
  SelectOption,
} from '../../../types';
import { storeName } from '../../../store';
import { DaysPeriodField, validateDaysPeriod } from '../days_period_field';
import { isInEnum } from '../../../../../utils';

export function validateUsedCouponCode(
  formItems: WooCommerceFormItem,
): boolean {
  const usedCouponCodeIsInvalid =
    !formItems.coupon_code_ids ||
    formItems.coupon_code_ids.length < 1 ||
    !isInEnum(formItems.operator, AnyValueTypes) ||
    !validateDaysPeriod(formItems);

  return !usedCouponCodeIsInvalid;
}

export function UsedCouponCodeFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WooCommerceFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilter } = useDispatch(storeName);

  useEffect(() => {
    if (!Array.isArray(segment.coupon_code_ids)) {
      void updateSegmentFilter({ coupon_code_ids: [] }, filterIndex);
    }
    if (!isInEnum(segment.operator, AnyValueTypes)) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  const coupons: Coupon[] = useSelect(
    (select) => select(storeName).getCoupons(),
    [],
  );
  const couponOptions = coupons.map((coupon) => ({
    value: coupon.id,
    label: coupon.name,
  }));

  return (
    <>
      <Grid.CenteredRow>
        <Select
          isMaxContentWidth
          key="select-operator-used-coupon-codes"
          value={segment.operator}
          onChange={(e): void => {
            void updateSegmentFilter({ operator: e.target.value }, filterIndex);
          }}
          automationId="select-operator-used-coupon-code"
        >
          <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
          <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
          <option value={AnyValueTypes.NONE}>
            {MailPoet.I18n.t('noneOf')}
          </option>
        </Select>
        <ReactSelect
          key="select-coupon-codes"
          isFullWidth
          isMulti
          placeholder={MailPoet.I18n.t('selectWooCouponCodes')}
          options={couponOptions}
          value={filter((option) => {
            if (!segment.coupon_code_ids) return undefined;
            return segment.coupon_code_ids.indexOf(option.value) !== -1;
          }, couponOptions)}
          onChange={(options: SelectOption[]): void => {
            void updateSegmentFilter(
              {
                coupon_code_ids: (options || []).map(
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
        <DaysPeriodField filterIndex={filterIndex} />
      </Grid.CenteredRow>
    </>
  );
}
