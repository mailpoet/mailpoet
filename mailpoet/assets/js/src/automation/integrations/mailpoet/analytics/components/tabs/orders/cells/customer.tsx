import { CustomerData } from '../../../../store';

export function CustomerCell({
  customer,
}: {
  customer: CustomerData;
}): JSX.Element {
  const name = [customer.first_name, customer.last_name]
    .filter(Boolean)
    .join(' ');

  const label = name || customer.email;

  return (
    <a
      className="mailpoet-analytics-orders__customer"
      href={`?page=mailpoet-subscribers#/edit/${customer.id}`}
    >
      <img src={customer.avatar} alt={label} width="20" />
      {label}
    </a>
  );
}
