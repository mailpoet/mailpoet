import { OrderData } from '../../../store';
import { OrderCell } from './cells/order';
import { CustomerCell } from './cells/customer';
import { ProductsCell } from './cells/products';
import { EmailCell } from './cells/email';
import { OrderStatusCell } from './cells/order_status';
import { formattedPrice } from '../../../formatter';
import { MailPoet } from '../../../../../../../mailpoet';

export function transformOrdersToRows(orders: OrderData[] | undefined) {
  return orders === undefined
    ? []
    : orders.map((order) => [
        {
          display: MailPoet.Date.format(new Date(order.date)),
          value: order.date,
        },
        {
          display: <OrderCell order={order.details} />,
          value: order.details.id,
        },
        {
          display: <CustomerCell customer={order.customer} />,
          value: order.customer.last_name,
        },
        {
          display: <ProductsCell order={order.details} />,
          value: null,
        },
        {
          display: <EmailCell order={order} />,
          value: order.email.subject,
        },
        {
          display: (
            <OrderStatusCell
              id={order.details.status.id}
              name={order.details.status.name}
            />
          ),
          value: order.details.status.id,
        },
        {
          display: formattedPrice(order.details.total),
          value: order.details.total,
        },
      ]);
}
