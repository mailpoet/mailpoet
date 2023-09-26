import { Heading } from 'common/typography/heading/heading';
import { MailPoet } from 'mailpoet';
import { Button } from 'common';
import { finishWizard } from '../../finish-wizard';

function MSSStepThirdPart(): JSX.Element {
  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardMSSThirdPartTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardMSSThirdPartFirstParagraph')}</p>
      <p>{MailPoet.I18n.t('welcomeWizardMSSThirdPartSecondParagraph')}</p>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <Button
        className="mailpoet-wizard-continue-button"
        type="button"
        onClick={() => finishWizard()}
        isFullWidth
      >
        {MailPoet.I18n.t('welcomeWizardMSSThirdPartButton')}
      </Button>
    </>
  );
}

export { MSSStepThirdPart };
