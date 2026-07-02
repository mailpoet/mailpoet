import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { OkIcon } from 'common/manage-sender-domain/icons';

type Props = {
  senderDomain: string;
};

export function SenderDomainAuthenticatedStatus({
  senderDomain,
}: Props): JSX.Element {
  return (
    <p
      className="mailpoet_sender_domain_authenticated"
      data-automation-id="sender-domain-authenticated-status"
    >
      <OkIcon />
      {createInterpolateElement(
        __('Sender domain <domain/> is authenticated.', 'mailpoet'),
        {
          domain: <strong>{senderDomain}</strong>,
        },
      )}
    </p>
  );
}
