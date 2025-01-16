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
import autocompleter from './tag-autocompleter';

type Tag = {
  key: string | number;
  label?: string;
};

async function fetchTags(include: number[], callback: (tags: Tag[]) => void) {
  const path = addQueryArgs('/wp/v2/product_tag/', { include });
  const data: { id: number; name: string }[] = await apiFetch({
    path,
    method: 'GET',
  });
  callback(data.map((item) => ({ key: item?.id, label: item?.name })));
}

export function Edit(): JSX.Element {
  const [current, setCurrent] = useState<Tag[]>([]);
  const { selectedStep } = useSelect((select) => ({
    selectedStep: select(storeName).getSelectedStep(),
  }));
  const tagIds: number[] = useMemo(
    () => (selectedStep.args?.tag_ids as number[]) ?? [],
    [selectedStep],
  );
  const [isBusy, setIsBusy] = useState(tagIds.length > 0);

  useEffect(() => {
    if (!isBusy) {
      return;
    }
    void fetchTags(tagIds, (tags: Tag[]) => {
      setCurrent(tags);
      setIsBusy(false);
    });
  }, [isBusy, tagIds]);
  return (
    <>
      <PanelBody opened>
        <PlainBodyTitle title={__('Tags', 'mailpoet')} />
        <Search
          disabled={isBusy}
          type="custom"
          autocompleter={autocompleter}
          className={`mailpoet-product-search ${isBusy ? 'is-busy' : ''}`}
          placeholder={__('Search for a tag', 'mailpoet')}
          selected={current}
          onChange={(items: Tag[]) => {
            setCurrent(items);
            void dispatch(storeName).updateStepArgs(
              selectedStep.id,
              'tag_ids',
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
