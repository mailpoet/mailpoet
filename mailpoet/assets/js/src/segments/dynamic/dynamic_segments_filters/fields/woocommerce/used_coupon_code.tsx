import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Grid } from 'common/grid';
import { Select } from 'common';
import { ReactSelect } from 'common/form/react_select/react_select';
import { MailPoet } from 'mailpoet';
import { filter, uniqBy, debounce } from 'lodash/fp';
import { APIErrorsNotice } from '../../../../../notices/api_errors_notice';
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
  // isInitialized is used for displaying loading message before loading coupons
  const [isInitialized, setIsInitialized] = useState<boolean>(false);
  const [coupons, setCoupons] = useState<SelectOption[]>([]);
  // additionalLoading state is used for displaying loading message when loading more coupons
  const [additionalLoading, setAdditionalLoading] = useState<boolean>(false);
  const [page, setPage] = useState<number>(1);
  // hasMore state is used to prevent loading more coupons when there are no more coupons to load
  const [hasMore, setHasMore] = useState<boolean>(true);
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [errors, setErrors] = useState([]);

  const loadCoupons = useCallback(
    (
      isInitialLoading: boolean,
      newPage: number,
      newSearchQuery: string,
      newHasMore: boolean,
    ) => {
      if (!newHasMore) {
        return;
      }

      if (!isInitialLoading) {
        setAdditionalLoading(true);
      }

      void MailPoet.Ajax.post({
        api_version: MailPoet.apiVersion,
        endpoint: 'coupons',
        action: 'getCoupons',
        data: {
          page_number: newPage,
          page_size: 1000,
          include_coupon_ids: segment.coupon_code_ids,
          search: newSearchQuery,
        },
      })
        .then((response) => {
          const { data } = response;
          const loadedCoupons: SelectOption[] = data.map((coupon: Coupon) => ({
            value: coupon.id.toString(),
            label: coupon.text,
          }));
          const nextPage = newPage + 1;
          if (loadedCoupons.length === 0) {
            setHasMore(false);
          } else {
            setCoupons((prevOptions: SelectOption[]) =>
              uniqBy(
                (coupon) => coupon.value,
                [...prevOptions, ...loadedCoupons],
              ),
            );
            setPage(nextPage);
          }
          if (!isInitialLoading) {
            setAdditionalLoading(false);
          }
        })
        .fail((response: ErrorResponse) => {
          setErrors(response.errors as { message: string }[]);
        });
    },
    [segment.coupon_code_ids],
  );

  /**
   * This function is called when user scrolls to the bottom of the select and should load more coupons.
   * Loading coupons should not be called when there are no more coupons to load.
   */
  const handleMenuScrollToBottom = () => {
    if (!additionalLoading && hasMore) {
      loadCoupons(false, page, searchQuery, hasMore);
    }
  };

  // Define a debounced version of handleInputChange using lodash/fp
  const debouncedHandleInputChange = debounce(300, (inputValue: string) => {
    const oldSearchQuery = searchQuery; // OldSearchQuery is used to prevent loading coupons when search query is deleted
    setSearchQuery(inputValue);
    if (
      !additionalLoading &&
      ((hasMore && inputValue) || (oldSearchQuery && !inputValue))
    ) {
      setPage(1);
      // Passing new values to loadCoupons to avoid using old values
      loadCoupons(false, 1, inputValue, hasMore);
    }
  });

  /**
   * Function for handling search input change that can filter coupons by search query when
   * there is more coupons than one page.
   * @param inputValue
   */
  const handleInputChange = (inputValue: string): void => {
    debouncedHandleInputChange(inputValue);
  };

  useEffect(() => {
    if (!isInitialized) {
      loadCoupons(true, page, searchQuery, hasMore);
      setIsInitialized(true);
    }
  }, [isInitialized, page, searchQuery, loadCoupons, hasMore]);

  useEffect(() => {
    if (!Array.isArray(segment.coupon_code_ids)) {
      void updateSegmentFilter({ coupon_code_ids: [] }, filterIndex);
    }
    if (!isInEnum(segment.operator, AnyValueTypes)) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      {errors.length > 0 && <APIErrorsNotice errors={errors} />}
      {isInitialized ? (
        <>
          <Grid.CenteredRow>
            <Select
              isMaxContentWidth
              key="select-operator-used-coupon-codes"
              value={segment.operator}
              onChange={(e): void => {
                void updateSegmentFilter(
                  { operator: e.target.value },
                  filterIndex,
                );
              }}
              automationId="select-operator-used-coupon-code"
            >
              <option value={AnyValueTypes.ANY}>
                {MailPoet.I18n.t('anyOf')}
              </option>
              <option value={AnyValueTypes.ALL}>
                {MailPoet.I18n.t('allOf')}
              </option>
              <option value={AnyValueTypes.NONE}>
                {MailPoet.I18n.t('noneOf')}
              </option>
            </Select>
            <ReactSelect
              key="select-coupon-codes"
              isFullWidth
              isMulti
              isLoadingMore={additionalLoading}
              placeholder={MailPoet.I18n.t('selectWooCouponCodes')}
              options={coupons}
              value={filter((option) => {
                if (!segment.coupon_code_ids) return undefined;
                return segment.coupon_code_ids.indexOf(option.value) !== -1;
              }, coupons)}
              onInputChange={handleInputChange}
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
              onMenuScrollToBottom={handleMenuScrollToBottom}
            />
          </Grid.CenteredRow>
          <Grid.CenteredRow>
            <DaysPeriodField filterIndex={filterIndex} />
          </Grid.CenteredRow>
        </>
      ) : (
        <Grid.CenteredRow>
          {__('Loading coupon codes...', 'mailpoet')}
        </Grid.CenteredRow>
      )}
    </>
  );
}
