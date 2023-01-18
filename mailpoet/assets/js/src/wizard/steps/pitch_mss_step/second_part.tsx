import { useHistory, useParams } from 'react-router-dom';
import { useState } from '@wordpress/element';
import { useCallback } from 'react';
import ReactStringReplace from 'react-string-replace';
import { MailPoet } from 'mailpoet';
import { Button, Input, Heading } from 'common';
import { OwnEmailServiceNote } from './own_email_service_note';

type MSSStepSecondPartPropType = {
  finishWizard: (redirect_url?: string) => void;
};

function MSSStepSecondPart({
  finishWizard,
}: MSSStepSecondPartPropType): JSX.Element {
  const [verifyButtonDisabled, setVerifyButtonDisabled] = useState(true);
  const history = useHistory();
  const { step } = useParams<{ step: string }>();

  const maybeEnableVerifyButton = useCallback((event) => {
    if (event.target.value) {
      setVerifyButtonDisabled(false);
    } else {
      setVerifyButtonDisabled(true);
    }
  }, []);

  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardMSSSecondPartTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardMSSSecondPartEnterKey')}</p>
      <p>
        {ReactStringReplace(
          MailPoet.I18n.t('welcomeWizardMSSSecondPartNoAccount'),
          /\[link](.*?)\[\/link]/g,
          (match, index) => (
            <a
              key={index}
              target="_blank"
              rel="noreferrer"
              href="https://account.mailpoet.com/?ref=plugin-wizard&utm_source=plugin&utm_medium=onboarding&utm_campaign=purchase"
            >
              {match}
            </a>
          ),
        )}
      </p>
      <div className="mailpoet-gap" />

      <label htmlFor="mailpoet-premium-key">
        <span className="mailpoet-wizard-label">
          {MailPoet.I18n.t('welcomeWizardMSSSecondPartInputLabel')}
        </span>
        <Input
          id="mailpoet-premium-key"
          name="mailpoet-premium-key"
          type="text"
          placeholder={MailPoet.I18n.t(
            'welcomeWizardMSSSecondPartInputPlaceholder',
          )}
          onChange={maybeEnableVerifyButton}
          isFullWidth
        />
      </label>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <Button
        type="button"
        isFullWidth
        isDisabled={verifyButtonDisabled}
        onClick={() => history.push(`/steps/${step}/part/3`)}
      >
        {MailPoet.I18n.t('welcomeWizardMSSSecondPartButton')}
      </Button>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <OwnEmailServiceNote finishWizard={finishWizard} />
    </>
  );
}

export { MSSStepSecondPart };
