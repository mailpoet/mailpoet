import { Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { OrderDetails } from '../../../../store';

export function OrderCell({ order }: { order: OrderDetails }): JSX.Element {
  return (
    <Tooltip text={__('Order details', 'mailpoet')}>
      <a href={`post.php?post=${order.id}&action=edit`}>{`${order.id}`}</a>
    </Tooltip>
  );
}
