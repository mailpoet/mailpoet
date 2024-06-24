import { __ } from '@wordpress/i18n';
import { close } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { Subscriber } from '../../../../store';
import { CustomerCell } from '../../orders/cells/customer';

type HeaderProps = {
  subscriber: Subscriber | null;
  onClose: () => void;
};

export function Header({ subscriber, onClose }: HeaderProps): JSX.Element {
  return (
    <div className="components-modal__header mailpoet-analytics-activity-modal-header">
      <h1 className="components-modal__header-heading">
        {__('Subscriber activity', 'mailpoet')}
        {subscriber && ': '}
      </h1>

      {subscriber && <CustomerCell customer={subscriber} />}
      <div className="mailpoet-analytics-activity-modal-header-spacer" />
      <Button onClick={onClose} icon={close} label={__('Close')} />
    </div>
  );
}
