import { useDispatch } from '@wordpress/data';
import { CustomerData, storeName } from '../../../../store';

type Props = {
  customer: CustomerData;
  isSample?: boolean;
};

export function CustomerCell({
  customer,
  isSample = false,
}: Props): JSX.Element {
  const { openPremiumModalForSampleData } = useDispatch(storeName);

  const name = [customer.first_name, customer.last_name]
    .filter(Boolean)
    .join(' ');

  const label = name || customer.email;

  return (
    <a
      className="mailpoet-analytics-orders__customer"
      onClick={(event) => {
        if (isSample) {
          event.preventDefault();
          void openPremiumModalForSampleData();
        }
      }}
      href={isSample ? '' : `?page=mailpoet-subscribers#/edit/${customer.id}`}
    >
      <img src={customer.avatar} alt={label} width="20" />
      {label}
    </a>
  );
}
