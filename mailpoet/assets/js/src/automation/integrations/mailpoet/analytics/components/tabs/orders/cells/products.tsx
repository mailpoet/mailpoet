import { ViewMoreList as WooViewMoreList } from '@woocommerce/components';
import { OrderDetails } from '../../../../store';

// WooViewMoreList has return type annotated as Object
const ViewMoreList = WooViewMoreList as unknown as (
  ...args: Parameters<typeof WooViewMoreList>
) => JSX.Element;

export function ProductsCell({ order }: { order: OrderDetails }) {
  const items =
    order.products.length > 0
      ? order.products.map((item) => (
          <span key={`key-${item.id}`}>
            {item.name}&nbsp;
            <span className="quantity">({item.quantity}&times;)</span>
          </span>
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
