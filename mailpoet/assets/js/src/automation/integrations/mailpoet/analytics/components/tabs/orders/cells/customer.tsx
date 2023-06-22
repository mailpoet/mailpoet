import { CustomerData } from '../../../../store';

export function CustomerCell({
  customer,
}: {
  customer: CustomerData;
}): JSX.Element {
  return (
    <a
      className="mailpoet-analytics-orders__customer"
      href={`?page=mailpoet-subscribers#/edit/${customer.id}`}
    >
      <img src={customer.avatar} alt={customer.last_name} />
      {`${customer.first_name} ${customer.last_name}`}
    </a>
  );
}
