import { useEffect } from 'react';
import { MailPoet } from 'mailpoet';
import { filter } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

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
    label: MailPoet.I18n.t('segmentsActiveSubscription'),
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
          <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
          <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
          <option value={AnyValueTypes.NONE}>
            {MailPoet.I18n.t('noneOf')}
          </option>
        </Select>
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <ReactSelect
          isMulti
          dimension="small"
          key="select-segment-category"
          isFullWidth
          placeholder={MailPoet.I18n.t('selectWooSubscription')}
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
