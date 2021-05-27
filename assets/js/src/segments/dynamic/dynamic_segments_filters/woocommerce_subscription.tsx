import React from 'react';
import MailPoet from 'mailpoet';
import { assign, compose, find } from 'lodash/fp';
import { useSelect } from '@wordpress/data';

import Select from 'common/form/react_select/react_select';

import {
  OnFilterChange,
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

interface Props {
  onChange: OnFilterChange;
  item: WooCommerceSubscriptionFormItem;
}

export const WooCommerceSubscriptionFields: React.FunctionComponent<Props> = (
  { onChange, item }
) => {
  const subscriptionProducts: WindowSubscriptionProducts = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSubscriptionProducts(),
    []
  );
  const productOptions = subscriptionProducts.map((product) => ({
    value: product.id,
    label: product.name,
  }));

  return (
    <>
      <div className="mailpoet-gap" />
      <Select
        placeholder={MailPoet.I18n.t('selectWooSubscription')}
        automationId="segment-woo-subscription-action"
        options={productOptions}
        value={find(['value', item.product_id], productOptions)}
        onChange={(option: SelectOption): void => compose([
          onChange,
          assign(item),
        ])({ product_id: option.value })}
      />
    </>
  );
};
