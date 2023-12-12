import { useEffect, useMemo, useState } from 'react';
import { Search } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { dispatch, useSelect } from '@wordpress/data';
import { PlainBodyTitle } from '../../../../../editor/components';
import { storeName } from '../../../../../editor/store';
import { OrderStatusPanel } from '../../order-status-changed/edit/order-status-panel';

type Product = {
  key: string | number;
  label?: string;
};

async function fetchProducts(
  include: number[],
  callback: (products: Product[]) => void,
) {
  const path = addQueryArgs('/wc-analytics/products/', { include });
  const data: { id: number; name: string }[] = await apiFetch({
    path,
    method: 'GET',
  });
  callback(data.map((item) => ({ key: item?.id, label: item?.name })));
}

export function Edit(): JSX.Element {
  const [current, setCurrent] = useState<Product[]>([]);
  const { selectedStep } = useSelect((select) => ({
    selectedStep: select(storeName).getSelectedStep(),
  }));
  const productIds: number[] = useMemo(
    () => (selectedStep.args?.product_ids as number[]) ?? [],
    [selectedStep],
  );
  const [isBusy, setIsBusy] = useState(productIds.length > 0);

  useEffect(() => {
    if (!isBusy) {
      return;
    }
    void fetchProducts(productIds, (products: Product[]) => {
      setCurrent(products);
      setIsBusy(false);
    });
  }, [isBusy, productIds]);
  return (
    <>
      <PanelBody opened>
        <PlainBodyTitle title={__('Products', 'mailpoet')} />
        <Search
          disabled={isBusy}
          type="products"
          className={`mailpoet-product-search ${isBusy ? 'is-busy' : ''}`}
          placeholder={__('Search for a product', 'mailpoet')}
          selected={current}
          onChange={(items: Product[]) => {
            setCurrent(items);
            void dispatch(storeName).updateStepArgs(
              selectedStep.id,
              'product_ids',
              items.map((item) => item.key),
            );
          }}
          multiple
          inlineTags
        />
      </PanelBody>

      <OrderStatusPanel
        label={__('Order settings', 'mailpoet')}
        showFrom={false}
        showTo
        toLabel={__('Order status', 'mailpoet')}
        onChange={(status, property) => {
          void dispatch(storeName).updateStepArgs(
            selectedStep.id,
            property,
            status,
          );
        }}
      />
    </>
  );
}
