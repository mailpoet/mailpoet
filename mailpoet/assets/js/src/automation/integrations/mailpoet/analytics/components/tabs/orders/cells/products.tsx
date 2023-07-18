import { ViewMoreList } from '@woocommerce/components';
import { Fragment } from '@wordpress/element';
import { OrderDetails } from '../../../../store';

export function ProductsCell({ order }: { order: OrderDetails }) {
  const items =
    order.products.length > 0
      ? order.products.map((item) => (
          <Fragment key={`key-${item.id}`}>
            {item.name}&nbsp;
            <span className="quantity">({item.quantity}&times;)</span>
          </Fragment>
        ))
      : [];

  if (!items.length) {
    return <span>—</span>;
  }

  const visibleItem = items.slice(0, 1);

  return (
    <div className="mailpoet-automations-analytics-order-products">
      {visibleItem}
      {items.length > 1 && <ViewMoreList items={items} />}
    </div>
  );
}
