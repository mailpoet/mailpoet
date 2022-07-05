import jQuery from 'jquery';
import { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import { AuthorizeSenderEmailModal } from 'common/authorize_sender_email_modal';

/**
 * Perform action after the email has been successfully Authorized
 */
const performSuccessActionOnModalClose = () => {
  // if in Settings, reload page, so the new saved FROM address is loaded
  const isInSettings = window.location.href.includes('?page=mailpoet-settings');

  const isInNewsletterSendPage = window.location.href.includes(
    '?page=mailpoet-newsletters#/send',
  );

  if (isInSettings) {
    window.location.reload();
  } else if (isInNewsletterSendPage) {
    jQuery('#field_sender_address')
      .parsley()
      .removeError('invalidFromAddress', { updateClass: true });
  }
};

function AuthorizeSenderEmailApp() {
  const [showModal, setShowModal] = useState(''); // used to hold the email address as well

  useEffect(() => {
    const performAction = (e) => {
      e.preventDefault();
      const email = String(e?.target?.dataset?.email || '');
      setShowModal(email);
    };
    // use jQuery since some of the targeted notices are added to the DOM using the old
    // jQuery-based notice implementation which doesn't trigger pure-JS added listeners
    jQuery(($) => {
      $(document).on(
        'click',
        '.notice .mailpoet-js-button-authorize-email',
        performAction,
      );

      $(document).on(
        'click',
        '.parsley-errors-list .mailpoet-js-button-authorize-email',
        performAction,
      );
    });
  }, []);

  return (
    <>
      {showModal && (
        <AuthorizeSenderEmailModal
          senderEmail={showModal}
          onRequestClose={() => {
            setShowModal('');
          }}
          setAuthorizedAddress={performSuccessActionOnModalClose}
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
