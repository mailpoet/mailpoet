import { useState } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import ReactStringReplace from 'react-string-replace';
import { MailPoet } from 'mailpoet';
import { Button } from 'common';

type OwnEmailDeliveryServicePropType = {
  finishWizard: (redirect_url?: string) => void;
};

function OwnEmailServiceNote({
  finishWizard,
}: OwnEmailDeliveryServicePropType): JSX.Element {
  const [confirmationModalIsOpen, setConfirmationModalOpen] = useState(false);
  const openConfirmationModal = (e) => {
    e.preventDefault();
    setConfirmationModalOpen(true);
  };
  const closeConfirmationModal = () => setConfirmationModalOpen(false);
  const finishWithOwnService = (e) => {
    e.preventDefault();
    finishWizard('admin.php?page=mailpoet-settings#/mta/other');
  };

  return (
    <>
      <p>
        {ReactStringReplace(
          MailPoet.I18n.t('welcomeWizardMSSAdvancedUsers'),
          /\[link](.*?)\[\/link]/g,
          (match, index) => (
            <a
              key={index}
              className="mailpoet-link"
              onClick={openConfirmationModal}
              href="#"
            >
              {match}
            </a>
          ),
        )}
      </p>
      {confirmationModalIsOpen && (
        <Modal
          className="mailpoet-welcome-wizard-confirmation-modal"
          title={MailPoet.I18n.t('welcomeWizardMSSConfirmationModalTitle')}
          onRequestClose={closeConfirmationModal}
        >
          <p>
            {window.mailpoet_mail_function_enabled
              ? MailPoet.I18n.t(
                  'welcomeWizardMSSConfirmationModalFirstParagraph',
                )
              : MailPoet.I18n.t(
                  'welcomeWizardMSSConfirmationModalFirstParagraphWithoutMailFunction',
                )}
          </p>
          <p>
            {MailPoet.I18n.t(
              'welcomeWizardMSSConfirmationModalSecondParagraph',
            )}
          </p>
          <div className="mailpoet-welcome-wizard-confirmation-modal-buttons">
            <Button variant="secondary" onClick={closeConfirmationModal}>
              {MailPoet.I18n.t('welcomeWizardMSSConfirmationModalGoBackButton')}
            </Button>
            <Button onClick={finishWithOwnService}>
              {MailPoet.I18n.t('welcomeWizardMSSConfirmationModalOkButton')}
            </Button>
          </div>
        </Modal>
      )}
    </>
  );
}

export { OwnEmailServiceNote };
