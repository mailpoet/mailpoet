import {
  ComponentProps,
  EventHandler,
  FocusEvent,
  KeyboardEvent,
  MouseEvent,
} from 'react';
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

type Props = Omit<ComponentProps<typeof Modal>, 'title' | 'onRequestClose'> & {
  // Fix type from "@types/wordpress__components" where it is defined as a union of event
  // handlers, resulting in a function requiring intersection of all of the event types.
  onRequestClose: EventHandler<KeyboardEvent | MouseEvent | FocusEvent>;
};

export function PremiumModal({ children, ...props }: Props): JSX.Element {
  return (
    <Modal
      className="mailpoet-premium-modal"
      title={__('Upgrade to premium', 'mailpoet')}
      closeButtonLabel={__('Cancel', 'mailpoet')}
      {...props}
    >
      <div>
        {__(
          'Google Analytics tracking is not available in the free version of the MailPoet plugin. Please upgrade to the Premium version.',
          'mailpoet',
        )}
      </div>
      <div className="mailpoet-premium-modal-footer">
        <Button variant="tertiary" onClick={props.onRequestClose}>
          {__('Cancel', 'mailpoet')}
        </Button>
        <Button variant="primary">{__('Upgrade', 'mailpoet')}</Button>
      </div>
    </Modal>
  );
}
