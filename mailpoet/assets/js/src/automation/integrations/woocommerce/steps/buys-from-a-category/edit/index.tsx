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

type Category = {
  key: string | number;
  label?: string;
};

async function fetchCategories(
  include: number[],
  callback: (products: Category[]) => void,
) {
  const path = addQueryArgs('/wc-analytics/products/categories/', { include });
  const data: { id: number; name: string }[] = await apiFetch({
    path,
    method: 'GET',
  });
  callback(data.map((item) => ({ key: item?.id, label: item?.name })));
}

export function Edit(): JSX.Element {
  const [current, setCurrent] = useState<Category[]>([]);
  const { selectedStep } = useSelect((select) => ({
    selectedStep: select(storeName).getSelectedStep(),
  }));
  const categoryIds: number[] = useMemo(
    () => (selectedStep.args?.category_ids as number[]) ?? [],
    [selectedStep],
  );
  const [isBusy, setIsBusy] = useState(categoryIds.length > 0);

  useEffect(() => {
    if (!isBusy) {
      return;
    }
    void fetchCategories(categoryIds, (categories: Category[]) => {
      setCurrent(categories);
      setIsBusy(false);
    });
  }, [isBusy, categoryIds]);
  return (
    <>
      <PanelBody opened>
        <PlainBodyTitle title={__('Categories', 'mailpoet')} />
        <Search
          disabled={isBusy}
          type="categories"
          className={`mailpoet-product-search ${isBusy ? 'is-busy' : ''}`}
          placeholder={__('Search for a category', 'mailpoet')}
          selected={current}
          onChange={(items: Category[]) => {
            setCurrent(items);
            void dispatch(storeName).updateStepArgs(
              selectedStep.id,
              'category_ids',
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
