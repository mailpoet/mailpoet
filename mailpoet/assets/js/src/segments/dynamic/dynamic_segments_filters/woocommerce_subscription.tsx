import { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { filter } from 'lodash/fp';
import { useDispatch, useSelect } from '@wordpress/data';

import { ReactSelect } from 'common/form/react_select/react_select';
import { Select } from 'common/form/select/select';
import { Grid } from 'common/grid';

import {
  AnyValueTypes,
  SegmentTypes,
  SelectOption,
  WindowSubscriptionProducts,
  WooCommerceSubscriptionFormItem,
} from '../types';

enum WooCommerceSubscriptionsActionTypes {
  ACTIVE_SUBSCRIPTIONS = 'hasActiveSubscription',
}

export const WooCommerceSubscriptionOptions = [
  {
    value: WooCommerceSubscriptionsActionTypes.ACTIVE_SUBSCRIPTIONS,
    label: __('has active subscription', 'mailpoet'),
    group: SegmentTypes.WooCommerceSubscription,
  },
];

export function validateWooCommerceSubscription(
  formItem: WooCommerceSubscriptionFormItem,
): boolean {
  const isIncomplete =
    !formItem.product_ids || !formItem.product_ids.length || !formItem.operator;
  if (
    formItem.action ===
      WooCommerceSubscriptionsActionTypes.ACTIVE_SUBSCRIPTIONS &&
    isIncomplete
  ) {
    return false;
  }
  return true;
}

type Props = {
  filterIndex: number;
};

export function WooCommerceSubscriptionFields({
  filterIndex,
}: Props): JSX.Element {
  const segment: WooCommerceSubscriptionFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } = useDispatch(
    'mailpoet-dynamic-segments-form',
  );

  const subscriptionProducts: WindowSubscriptionProducts = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSubscriptionProducts(),
    [],
  );
  const productOptions = subscriptionProducts.map((product) => ({
    value: product.id,
    label: product.name,
  }));

  useEffect(() => {
    if (
      segment.action ===
        WooCommerceSubscriptionsActionTypes.ACTIVE_SUBSCRIPTIONS &&
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
          key="select-operator"
          value={segment.operator}
          onChange={(e) =>
            updateSegmentFilterFromEvent('operator', filterIndex, e)
          }
          automationId="select-operator"
        >
          <option value={AnyValueTypes.ANY}>{__('any of', 'mailpoet')}</option>
          <option value={AnyValueTypes.ALL}>{__('all of', 'mailpoet')}</option>
          <option value={AnyValueTypes.NONE}>
            {__('none of', 'mailpoet')}
          </option>
        </Select>
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <ReactSelect
          isMulti
          dimension="small"
          key="select-segment-category"
          isFullWidth
          placeholder={__('Search subscriptions', 'mailpoet')}
          options={productOptions}
          value={filter((option) => {
            if (!segment.product_ids) return false;
            return segment.product_ids.indexOf(option.value) !== -1;
          }, productOptions)}
          onChange={(options: SelectOption[]): void => {
            void updateSegmentFilter(
              {
                product_ids: (options || []).map((x: SelectOption) => x.value),
              },
              filterIndex,
            );
          }}
          automationId="select-segment-products"
        />
      </Grid.CenteredRow>
    </>
  );
}
