import {
  ComponentProps,
  EventHandler,
  FocusEvent,
  KeyboardEvent,
  MouseEvent,
} from 'react';
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { getUpgradeInfo, premiumFeaturesEnabled } from './upgrade_info';

const premiumValidAndActive = premiumFeaturesEnabled && MailPoet.premiumActive;
const upgradeInfo = getUpgradeInfo();

type Props = Omit<ComponentProps<typeof Modal>, 'title' | 'onRequestClose'> & {
  // Fix type from "@types/wordpress__components" where it is defined as a union of event
  // handlers, resulting in a function requiring intersection of all of the event types.
  onRequestClose: EventHandler<KeyboardEvent | MouseEvent | FocusEvent>;
};

export function PremiumModal({ children, ...props }: Props): JSX.Element {
  return (
    <Modal
      className="mailpoet-premium-modal"
      title={upgradeInfo.title}
      closeButtonLabel={__('Cancel', 'mailpoet')}
      {...props}
    >
      <div>
        {!premiumValidAndActive && children} {upgradeInfo.info}
      </div>
      <div className="mailpoet-premium-modal-footer">
        <Button variant="tertiary" onClick={props.onRequestClose}>
          {__('Cancel', 'mailpoet')}
        </Button>
        <Button
          variant="primary"
          href={upgradeInfo.url}
          target="_blank"
          rel="noopener noreferrer"
        >
          {upgradeInfo.cta}
        </Button>
      </div>
    </Modal>
  );
}
