import { OrderSection } from '../../../store';
import { OrderCell } from './cells/order';
import { CustomerCell } from './cells/customer';
import { ProductsCell } from './cells/products';
import { EmailCell } from './cells/email';
import { StatusCell } from './cells/status';
import { formattedPrice } from '../../../formatter';
import { MailPoet } from '../../../../../../../mailpoet';

export function transformOrdersToRows(data: OrderSection['data'] | undefined) {
  const orders = data?.items;
  return orders === undefined
    ? []
    : orders.map((order) => [
        {
          display: MailPoet.Date.format(new Date(order.date)),
          value: order.date,
        },
        {
          display: (
            <OrderCell order={order.details} isSample={data?.isSample} />
          ),
          value: order.details.id,
        },
        {
          display: (
            <CustomerCell customer={order.customer} isSample={data?.isSample} />
          ),
          value: order.customer.last_name,
        },
        {
          display: <ProductsCell order={order.details} />,
          value: null,
        },
        {
          display: <EmailCell order={order} isSample={data?.isSample} />,
          value: order.email.subject,
        },
        {
          display: (
            <StatusCell
              status={order.details.status.id}
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
