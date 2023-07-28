import { useDispatch } from '@wordpress/data';
import { Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { OrderDetails, storeName } from '../../../../store';

type Props = {
  order: OrderDetails;
  isSample?: boolean;
};

export function OrderCell({ order, isSample = false }: Props): JSX.Element {
  const { openPremiumModalForSampleData } = useDispatch(storeName);

  return (
    <Tooltip text={__('Order details', 'mailpoet')}>
      <a
        onClick={(event) => {
          if (isSample) {
            event.preventDefault();
            void openPremiumModalForSampleData();
          }
        }}
        href={isSample ? '' : `post.php?post=${order.id}&action=edit`}
      >{`${order.id}`}</a>
    </Tooltip>
  );
}
