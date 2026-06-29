import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { Notice } from '@wordpress/ui';
import { extractEmailDomain } from 'common/functions';
import { UseSenderFields } from '../shared/use-sender-fields';
import {
  getSenderAuthorizationState,
  isFreeMailSenderAddress,
} from './sender-notice-utils';

type Props = {
  senderFields: UseSenderFields;
};

const storeDomain = window.location.hostname.replace('www.', '');

function getProviderName(domain: string): string {
  const [label] = domain.split('.');
  if (!label) {
    return domain;
  }
  return label.charAt(0).toUpperCase() + label.slice(1);
}

// The recommended sender is an already-authorized sender address on the store's
// own domain. When none is available we cannot recommend a switch, so the
// "Use recommended sender" action is hidden.
function getRecommendedSender(): string | null {
  const authorizedEmails = window.mailpoet_authorized_emails ?? [];
  return (
    authorizedEmails.find(
      (email) => extractEmailDomain(email) === storeDomain,
    ) ?? null
  );
}

export function FreeMailSenderNotice({
  senderFields,
}: Props): JSX.Element | null {
  const [isDismissed, setIsDismissed] = useState(false);
  const { createSuccessNotice } = useDispatch(noticesStore);
  const { senderAddress, setSenderAddress, setReplyToAddress } = senderFields;

  const isFreeMailSender = isFreeMailSenderAddress(senderAddress);
  // While the sender email still needs authorization, show only the authorization notice.
  // The free-mail notice appears once the address is authorized.
  const { showEmailAuthorization } = getSenderAuthorizationState(senderFields);

  if (!isFreeMailSender || isDismissed || showEmailAuthorization) {
    return null;
  }

  const recommendedSender = getRecommendedSender();
  const providerName = getProviderName(extractEmailDomain(senderAddress));

  const handleUseRecommendedSender = () => {
    if (!recommendedSender) {
      return;
    }
    // Keep the current free-mail address reachable as Reply-to and switch the
    // From address to the authorized store-domain sender.
    setReplyToAddress(senderAddress);
    setSenderAddress(recommendedSender);
    void createSuccessNotice(__('Sender email address updated.', 'mailpoet'), {
      type: 'snackbar',
    });
  };

  const handleKeepCurrentSender = (
    event: React.MouseEvent<HTMLAnchorElement>,
  ) => {
    event.preventDefault();
    setIsDismissed(true);
  };

  return (
    <Notice.Root intent="warning">
      <Notice.Title>
        {__('Subscribers may miss this email', 'mailpoet')}
      </Notice.Title>
      <Notice.Description>
        {sprintf(
          /* translators: %s is the sender email provider name, e.g. "Gmail". */
          __(
            '%s works better as Reply-to. Use an available store-domain sender so this email is more likely to reach inboxes.',
            'mailpoet',
          ),
          providerName,
        )}
      </Notice.Description>
      <Notice.Actions>
        {recommendedSender && (
          <Notice.ActionButton onClick={handleUseRecommendedSender}>
            {__('Use recommended sender', 'mailpoet')}
          </Notice.ActionButton>
        )}
        <Notice.ActionLink href="#" onClick={handleKeepCurrentSender}>
          {__('Keep current sender', 'mailpoet')}
        </Notice.ActionLink>
      </Notice.Actions>
    </Notice.Root>
  );
}
