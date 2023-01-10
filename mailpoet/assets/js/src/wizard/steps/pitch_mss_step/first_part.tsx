import { external, Icon } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import ReactStringReplace from 'react-string-replace';
import { Heading } from 'common/typography/heading/heading';
import { MailPoet } from 'mailpoet';
import { List } from 'common/typography/list/list';
import { Button } from 'common';

type OwnEmailDeliveryServicePropType = {
  finishWizard: (redirect_url?: string) => void;
};

function OwnEmailDeliveryService({
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

const mailpoetAccountUrl = MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
  MailPoet.subscribersCount,
  MailPoet.currentWpUserEmail,
  'starter',
  {
    utm_medium: 'onboarding',
    utm_campaign: 'purchase',
  },
);

type MSSStepFirstPartPropType = {
  subscribersCount: number;
  finishWizard: (redirect_url?: string) => void;
  setStepPart: (newPart: string) => void;
};

function MSSStepFirstPart({
  subscribersCount,
  finishWizard,
  setStepPart,
}: MSSStepFirstPartPropType): JSX.Element {
  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardMSSFirstPartTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardMSSFirstPartSubtitle')}</p>
      <div className="mailpoet-gap" />

      <List>
        <li>{MailPoet.I18n.t('welcomeWizardMSSList1')}</li>
        <li>{MailPoet.I18n.t('welcomeWizardMSSList2')}</li>
        {subscribersCount < 1000 ? (
          <li>{MailPoet.I18n.t('welcomeWizardMSSList3Free')}</li>
        ) : (
          <li>{MailPoet.I18n.t('welcomeWizardMSSList3Paid')}</li>
        )}
      </List>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <Button
        isFullWidth
        href={mailpoetAccountUrl}
        target="_blank"
        rel="noopener noreferrer"
        onClick={(event) => {
          event.preventDefault();
          window.open(mailpoetAccountUrl);
          setStepPart('second');
        }}
        iconEnd={<Icon icon={external} />}
      >
        {MailPoet.I18n.t('welcomeWizardMSSFirstPartButton')}
      </Button>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <OwnEmailDeliveryService finishWizard={finishWizard} />
    </>
  );
}

export { MSSStepFirstPart };
