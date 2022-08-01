import jQuery from 'jquery';
import { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import { MailPoet } from 'mailpoet';
import { extractPageNameFromUrl } from 'common/functions';
import { AuthorizeSenderEmailAndDomainModal } from 'common/authorize_sender_email_and_domain_modal';

const trackEvent = (data) => {
  const page = `${extractPageNameFromUrl() || 'some other'} page`;

  if (data && data.type && data.type === 'email') {
    MailPoet.trackEvent('MSS in plugin authorize email', {
      'authorized email source': 'modal',
      'original page': page,
      wasSuccessful: 'yes',
    });
  } else if (data && data.type && data.type === 'domain') {
    MailPoet.trackEvent('MSS in plugin verify sender domain', {
      'verify sender domain source': 'modal',
      'original page': page,
      wasSuccessful: 'yes',
    });
  }
};

/**
 * Perform action after the email has been successfully Authorized
 */
const performSuccessActionOnModalClose = (data) => {
  // if in Settings, reload page, so the new saved FROM address is loaded
  const isInSettings = window.location.href.includes('?page=mailpoet-settings');

  const isInNewsletterSendPage = window.location.href.includes(
    '?page=mailpoet-newsletters',
  );

  if (isInSettings) {
    trackEvent(data);
    window.location.reload();
  } else if (isInNewsletterSendPage) {
    trackEvent(data);
    jQuery('#field_sender_address')
      .parsley()
      .removeError('invalidFromAddress', { updateClass: true });
    jQuery('#field_sender_address')
      .parsley()
      .removeError('invalidSenderDomain', { updateClass: true });
  } else {
    trackEvent(data);
  }
};

function AuthorizeSenderEmailApp() {
  const [showModal, setShowModal] = useState(''); // used to hold the email address as well
  const [actionType, setActionType] = useState('email'); // use email as default for backwards compatibility

  useEffect(() => {
    const performAction = (e) => {
      e.preventDefault();
      const email = String(e?.target?.dataset?.email || '');
      const type = String(e?.target?.dataset?.type || '');

      if (type) {
        setActionType(type);
      } else {
        setActionType('email'); // fallback for when type is not provided
      }

      setShowModal(email);
    };
    // use jQuery since some of the targeted notices are added to the DOM using the old
    // jQuery-based notice implementation which doesn't trigger pure-JS added listeners
    jQuery(($) => {
      $(document).on(
        'click',
        '.mailpoet-js-button-authorize-email-and-sender-domain',
        performAction,
      );
    });
  }, []);

  return (
    <>
      {showModal && (
        <AuthorizeSenderEmailAndDomainModal
          senderEmail={showModal}
          onRequestClose={() => {
            setShowModal('');
          }}
          onSuccessAction={performSuccessActionOnModalClose}
          showSenderEmailTab={actionType === 'email'}
          showSenderDomainTab={actionType === 'domain'}
          initialTab={
            actionType === 'domain' ? 'sender_domain' : 'sender_email'
          }
        />
      )}
    </>
  );
}

// nothing is actually rendered to the container because the <Modal> component uses
// ReactDOM.createPortal() but we need an element as a React root on all pages
const container = document.getElementById(
  'mailpoet_authorize_sender_email_modal',
);
if (container) {
  ReactDOM.render(<AuthorizeSenderEmailApp />, container);
}
