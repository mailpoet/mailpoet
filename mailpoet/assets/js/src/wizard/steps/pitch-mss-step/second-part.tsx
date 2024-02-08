import { useHistory, useParams } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';
import { MailPoet } from 'mailpoet';
import { Heading } from 'common';
import { KeyActivationButton } from 'common/premium-key/key-activation-button';
import { KeyInput } from 'common/premium-key/key-input';
import { useEffect } from 'react';
import { useSelector } from 'settings/store/hooks';
import { OwnEmailServiceNote } from './own-email-service-note';
import { navigateToPath } from '../../navigate-to-path';

function MSSStepSecondPart(): JSX.Element {
  const history = useHistory();
  const { step } = useParams<{ step: string }>();
  const state = useSelector('getKeyActivationState')();

  useEffect(() => {
    if (state.isKeyValid === true) {
      navigateToPath(history, `/steps/${step}/part/3`, true);
    }
  }, [state.isKeyValid, history, step]);

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

      <label htmlFor="mailpoet_premium_key">
        <span className="mailpoet-wizard-label">
          {MailPoet.I18n.t('welcomeWizardMSSSecondPartInputLabel')}
        </span>
        <KeyInput
          placeholder={MailPoet.I18n.t(
            'welcomeWizardMSSSecondPartInputPlaceholder',
          )}
          isFullWidth
        />
      </label>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <KeyActivationButton
        label={MailPoet.I18n.t('welcomeWizardMSSSecondPartButton')}
        isFullWidth
      />

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <OwnEmailServiceNote />
    </>
  );
}

export { MSSStepSecondPart };
