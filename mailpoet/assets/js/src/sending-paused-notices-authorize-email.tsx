import jQuery from 'jquery';
import { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import { AuthorizeSenderEmailModal } from 'common/authorize_sender_email_modal';

const performActionOnModalClose = () => {
  // if in Settings, reload page, so the new saved FROM address is loaded
  const isInSettings = window.location.href.includes('?page=mailpoet-settings');
  if (isInSettings) {
    window.location.reload();
  }
};

function AuthorizeSenderEmailApp() {
  const [showModal, setShowModal] = useState(''); // used to hold the email address as well

  useEffect(() => {
    // use jQuery since some of the targeted notices are added to the DOM using the old
    // jQuery-based notice implementation which doesn't trigger pure-JS added listeners
    jQuery(($) => {
      $(document).on(
        'click',
        '.notice .mailpoet-js-button-authorize-email',
        (e) => {
          e.preventDefault();
          const email = String(e?.target?.dataset?.email || '');
          setShowModal(email);
        },
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
          setAuthorizedAddress={performActionOnModalClose}
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
