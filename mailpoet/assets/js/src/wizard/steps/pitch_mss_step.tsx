import { MailPoet } from 'mailpoet';
import { Icon, external } from '@wordpress/icons';
import ReactStringReplace from 'react-string-replace';
import { Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Button } from '../../common';
import { Heading } from '../../common/typography/heading/heading';
import { List } from '../../common/typography/list/list';

type ControlsPropType = {
  mailpoetAccountUrl: string;
  next: () => void;
  nextButtonText: string;
};

function Controls({
  mailpoetAccountUrl,
  next,
  nextButtonText,
}: ControlsPropType): JSX.Element {
  return (
    <>
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
          next();
        }}
        iconEnd={<Icon icon={external} />}
      >
        {nextButtonText}
      </Button>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />
    </>
  );
}

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

type WelcomeWizardPitchMSSStepPropType = {
  subscribersCount: number;
  next: () => void;
  finishWizard: (redirect_url?: string) => void;
};

function WelcomeWizardPitchMSSStep({
  subscribersCount,
  next,
  finishWizard,
}: WelcomeWizardPitchMSSStepPropType): JSX.Element {
  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardMSSFreeTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardMSSFreeSubtitle')}</p>
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

      <Controls
        mailpoetAccountUrl={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
          MailPoet.subscribersCount,
          MailPoet.currentWpUserEmail,
          'starter',
          { utm_medium: 'onboarding', utm_campaign: 'purchase' },
        )}
        next={next}
        nextButtonText={MailPoet.I18n.t('welcomeWizardMSSFreeButton')}
      />

      <OwnEmailDeliveryService finishWizard={finishWizard} />
    </>
  );
}

export { WelcomeWizardPitchMSSStep };
