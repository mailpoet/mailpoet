import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Button } from 'common';
import { useState } from '@wordpress/element';

function SendingMethodConfirmationModal(): JSX.Element {
  const [confirmationModalIsOpen, setConfirmationModalOpen] = useState(true);

  const closeSendingMethodConfirmationModal = () =>
    setConfirmationModalOpen(false);

  return (
    confirmationModalIsOpen && (
      <Modal
        className="mailpoet-welcome-wizard-confirmation-modal"
        title={__('Confirm sending service selection', 'mailpoet')}
        onRequestClose={() => {}} // users aren't permitted to close
        isDismissible={false}
      >
        <p>
          {__(
            'Your currently selected sending method is "MailPoet Sending Service". The API key you entered is not valid for sending with our sending service. You can either upgrade to a subscription which allows you sending with our service or choose a different',
            'mailpoet',
          )}
        </p>
        <div className="mailpoet-welcome-wizard-confirmation-modal-buttons">
          <Button
            onClick={() => {
              closeSendingMethodConfirmationModal();
              window.location.href =
                'admin.php?page=mailpoet-settings#/mta/other';
            }}
          >
            {__('Update service settings', 'mailpoet')}
          </Button>
        </div>
      </Modal>
    )
  );
}

export { SendingMethodConfirmationModal };
