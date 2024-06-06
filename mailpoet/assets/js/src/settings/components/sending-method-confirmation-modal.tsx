import { useState } from 'react';
import { createInterpolateElement } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Button } from 'common';

function SendingMethodConfirmationModal(): JSX.Element {
  const [confirmationModalIsOpen, setConfirmationModalOpen] = useState(true);

  const closeSendingMethodConfirmationModal = () =>
    setConfirmationModalOpen(false);

  return (
    confirmationModalIsOpen && (
      <Modal
        className="mailpoet-welcome-wizard-confirmation-modal"
        title={__('Confirm sending service selection', 'mailpoet')}
        onRequestClose={() => {}} // users aren't permitted to close the modal
        isDismissible={false}
      >
        <p>
          {createInterpolateElement(
            __(
              'Your currently selected sending method is "MailPoet Sending Service". The API key you entered is not valid for sending with our sending service. You can either <link>upgrade to a subscription</link> that allows you to send with our service or choose a different sending service',
              'mailpoet',
            ),
            {
              link: (
                <a
                  href={`https://account.mailpoet.com/?s=${window.mailpoet_subscribers_count}&utm_source=plugin&utm_medium=settings&utm_campaign=switch-to-sending-plan&ref=settings-key-activation`}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {' '}
                  &nbsp;{' '}
                </a>
              ),
            },
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
