import { useEffect, useState } from 'react';
import { PanelBody } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { getContext } from '../../../context';
import { storeName } from '../../../../../editor/store';
import {
  PlainBodyTitle,
  FormTokenField,
} from '../../../../../editor/components';
import { Form } from '../../../../../../segments/dynamic/form';
import { createStore } from '../../../../../../segments/dynamic/store/store';

export function ListPanel(): JSX.Element {
  const [storeLoaded, setStoreLoaded] = useState(false);

  useEffect(() => {
    const context = (getContext() as any).dynamic_segments;
    const w = window as any;
    w.mailpoet_products = context.mailpoet_products;
    w.mailpoet_static_segments_list = context.mailpoet_static_segments_list;
    w.mailpoet_membership_plans = context.mailpoet_membership_plans;
    w.mailpoet_subscription_products = context.mailpoet_subscription_products;
    w.mailpoet_product_categories = context.mailpoet_product_categories;
    w.mailpoet_newsletters_list = context.mailpoet_newsletters_list;
    w.wordpress_editable_roles_list = context.wordpress_editable_roles_list;
    w.mailpoet_can_use_woocommerce_memberships =
      context.mailpoet_can_use_woocommerce_memberships;
    w.mailpoet_can_use_woocommerce_subscriptions =
      context.mailpoet_can_use_woocommerce_subscriptions;
    w.mailpoet_woocommerce_currency_symbol =
      context.mailpoet_woocommerce_currency_symbol;
    w.mailpoet_woocommerce_countries = context.mailpoet_woocommerce_countries;
    w.mailpoet_custom_fields = context.mailpoet_custom_fields;
    w.mailpoet_tags = context.mailpoet_tags;
    w.mailpoet_can_use_woocommerce_subscriptions =
      context.mailpoet_can_use_woocommerce_subscriptions;
    w.mailpoet_can_use_woocommerce_memberships =
      context.mailpoet_can_use_woocommerce_memberships;
    createStore();
    setStoreLoaded(true);
  }, []);

  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  const rawSelected = selectedStep.args?.segment_ids
    ? (selectedStep.args.segment_ids as number[])
    : [];

  const validSegments = getContext().segments.filter(
    (segment) => segment.type === 'default',
  );
  const selected = validSegments.filter((segment): boolean =>
    rawSelected.includes(segment.id as number),
  );
  return (
    <PanelBody opened>
      <PlainBodyTitle title={__('Trigger settings', 'mailpoet')} />

      <FormTokenField
        label={__(
          'When someone subscribes to the following lists:',
          'mailpoet',
        )}
        placeholder={__('Any list', 'mailpoet')}
        value={selected}
        suggestions={validSegments}
        onChange={(values) => {
          dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'segment_ids',
            values.map((item) => item.id),
          );
        }}
      />

      {storeLoaded && <Form />}
    </PanelBody>
  );
}
