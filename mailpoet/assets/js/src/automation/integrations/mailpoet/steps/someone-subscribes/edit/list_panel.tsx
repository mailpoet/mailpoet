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
import { createStore } from '../../../../../../segments/dynamic/store/store';
import { FormBody } from '../../../../../../segments/dynamic/form_body';

export function ListPanel(): JSX.Element {
  const [storeLoaded, setStoreLoaded] = useState(false);

  useEffect(() => {
    const context = (getContext() as any).dynamic_segments;
    const w = window as any;
    w.mailpoet_products = context.products;
    w.mailpoet_static_segments_list = context.static_segments_list;
    w.mailpoet_membership_plans = context.membership_plans;
    w.mailpoet_subscription_products = context.subscription_products;
    w.mailpoet_product_categories = context.product_categories;
    w.mailpoet_newsletters_list = context.newsletters_list;
    w.wordpress_editable_roles_list = context.wordpress_editable_roles_list;
    w.mailpoet_can_use_woocommerce_memberships =
      context.can_use_woocommerce_memberships;
    w.mailpoet_can_use_woocommerce_subscriptions =
      context.can_use_woocommerce_subscriptions;
    w.mailpoet_woocommerce_currency_symbol =
      context.woocommerce_currency_symbol;
    w.mailpoet_woocommerce_countries = context.woocommerce_countries;
    w.mailpoet_custom_fields = context.custom_fields;
    w.mailpoet_tags = context.tags;
    w.mailpoet_can_use_woocommerce_subscriptions =
      context.can_use_woocommerce_subscriptions;
    w.mailpoet_can_use_woocommerce_memberships =
      context.can_use_woocommerce_memberships;
    createStore();
    //document
    //  .getElementById('wp-i18n-js-after')
    //  .insertAdjacentHTML('afterend', context.translations);
    //eval(context.translations);
    console.log(context.translations);
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

      {storeLoaded && (
        <FormBody
          onSubmit={(s) => {
            console.log(s);
            //setSegment(s);
          }}
        />
      )}
    </PanelBody>
  );
}
