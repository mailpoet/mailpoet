import { useEffect } from 'react';
import { MailPoet } from 'mailpoet';
import { extractEmailDomain, extractPageNameFromUrl } from 'common/functions';
import { AuthorizeSenderEmailModal } from './authorize-sender-email-modal';
import { AuthorizeSenderDomainModal } from './authorize-sender-domain-modal';

const trackEvent = (type: 'email' | 'domain') => {
  const page = `${extractPageNameFromUrl() || 'some other'} page`;

  if (type === 'email') {
    MailPoet.trackEvent('MSS in plugin authorize email', {
      'attempt to authorize': type,
      'original page': page,
    });
  } else if (type === 'domain') {
    MailPoet.trackEvent('MSS in plugin verify sender domain', {
      'attempt to authorize': type,
      'original page': page,
    });
  }
};

type Props = {
  onRequestClose: () => void;
  senderEmail: string;
  onSuccessAction: (param: { type: 'email' | 'domain'; data: string }) => void;
  showSenderEmailTab: boolean;
  showSenderDomainTab: boolean;
  initialTab: 'sender_email' | 'sender_domain';
  autoSwitchTab?: (param: 'sender_email' | 'sender_domain') => void;
};

function AuthorizeSenderEmailAndDomainModal({
  onRequestClose,
  senderEmail,
  onSuccessAction = () => {},
  showSenderEmailTab = false,
  showSenderDomainTab = false,
  initialTab = 'sender_email',
  autoSwitchTab = () => {},
}: Props): JSX.Element {
  useEffect(() => {
    if (!showSenderEmailTab && !showSenderDomainTab) {
      onRequestClose(); // close modal
    }
  });

  useEffect(() => {
    if (!senderEmail) return;

    if (
      initialTab === 'sender_email' &&
      showSenderEmailTab === false &&
      showSenderDomainTab
    ) {
      // email address is verified but pending sender domain verification
      // inform parent component to autoswitch tab and load sender domain tab
      autoSwitchTab('sender_domain');
    }
    if (
      initialTab === 'sender_domain' &&
      showSenderDomainTab === false &&
      showSenderEmailTab
    ) {
      // sender domain is verified but pending email address authorization
      // load email authorize tab
      autoSwitchTab('sender_email');
    }

    if (showSenderEmailTab) {
      trackEvent('email');
    }

    if (showSenderDomainTab) {
      trackEvent('domain');
    }
  }, [
    senderEmail,
    showSenderEmailTab,
    showSenderDomainTab,
    initialTab,
    autoSwitchTab,
  ]);

  const emailAddressDomain = extractEmailDomain(senderEmail);

  return showSenderEmailTab ? (
    <AuthorizeSenderEmailModal
      useModal
      senderEmail={senderEmail}
      onRequestClose={onRequestClose}
      setAuthorizedAddress={(authorizedEmailAddress) => {
        onSuccessAction({
          type: 'email',
          data: authorizedEmailAddress,
        });
      }}
    />
  ) : (
    <AuthorizeSenderDomainModal
      useModal
      senderDomain={emailAddressDomain}
      onRequestClose={onRequestClose}
      setVerifiedSenderDomain={(verifiedSenderDomain) => {
        onSuccessAction({ type: 'domain', data: verifiedSenderDomain });
      }}
    />
  );
}

export { AuthorizeSenderEmailAndDomainModal };
