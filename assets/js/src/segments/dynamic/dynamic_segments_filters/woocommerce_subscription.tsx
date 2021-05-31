import React from 'react';
import MailPoet from 'mailpoet';
import { find } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import Select from 'common/form/react_select/react_select';

import {
  SegmentTypes,
  SelectOption,
  WindowSubscriptionProducts,
  WooCommerceSubscriptionFormItem,
} from '../types';

enum WooCommerceSubscriptionsActionTypes {
  ACTIVE_SUBSCRIPTIONS = 'hasActiveSubscription',
}

export const WooCommerceSubscriptionOptions = [
  { value: WooCommerceSubscriptionsActionTypes.ACTIVE_SUBSCRIPTIONS, label: MailPoet.I18n.t('segmentsActiveSubscription'), group: SegmentTypes.WooCommerceSubscription },
];

export function validateWooCommerceSubscription(
  formItems: WooCommerceSubscriptionFormItem
): boolean {
  if (
    formItems.action === WooCommerceSubscriptionsActionTypes.ACTIVE_SUBSCRIPTIONS
    && !formItems.product_id
  ) {
    return false;
  }
  return true;
}

export const WooCommerceSubscriptionFields: React.FunctionComponent = () => {
  const segment: WooCommerceSubscriptionFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { updateSegment } = useDispatch('mailpoet-dynamic-segments-form');

  const subscriptionProducts: WindowSubscriptionProducts = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSubscriptionProducts(),
    []
  );
  const productOptions = subscriptionProducts.map((product) => ({
    value: product.id,
    label: product.name,
  }));

  return (
    <div>
      <Select
        dimension="small"
        placeholder={MailPoet.I18n.t('selectWooSubscription')}
        automationId="segment-woo-subscription-action"
        options={productOptions}
        value={find(['value', segment.product_id], productOptions)}
        onChange={(option: SelectOption): void => {
          updateSegment({ product_id: option.value });
        }}
      />
    </div>
  );
};
