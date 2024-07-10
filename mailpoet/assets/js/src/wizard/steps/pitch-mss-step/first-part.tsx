import { useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { external, Icon } from '@wordpress/icons';
import { Heading } from 'common/typography/heading/heading';
import { MailPoet } from 'mailpoet';
import { Button, List } from 'common';
import { OwnEmailServiceNote } from './own-email-service-note';
import { useSelector } from '../../../settings/store/hooks';
import { navigateToPath } from '../../navigate-to-path';

const mailpoetAccountUrl =
  'https://account.mailpoet.com/?ref=plugin-wizard&utm_source=plugin&utm_medium=onboarding&utm_campaign=purchase';

function openMailPoetShopAndGoToTheNextPart(event, navigate, step: string) {
  event.preventDefault();
  window.open(mailpoetAccountUrl);
  navigateToPath(navigate, `/steps/${step}/part/2`);
}

function MSSStepFirstPart(): JSX.Element {
  const navigate = useNavigate();
  const { step } = useParams<{ step: string }>();
  const state = useSelector('getKeyActivationState')();

  useEffect(() => {
    if (state.isKeyValid === true) {
      navigateToPath(navigate, `/steps/${step}/part/3`, true);
    }
  }, [state.isKeyValid, navigate, step]);

  return (
    <>
      <Heading level={1}>
        {MailPoet.I18n.t('welcomeWizardMSSFirstPartTitle')}
      </Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('welcomeWizardMSSFirstPartSubtitle')}</p>
      <div className="mailpoet-gap" />

      <div className="mailpoet-welcome-wizard-mss-list">
        <List>
          <li>{MailPoet.I18n.t('welcomeWizardMSSList1')}</li>
          <li>{MailPoet.I18n.t('welcomeWizardMSSList2')}</li>
          {MailPoet.subscribersCount < 1000 ? (
            <li>{MailPoet.I18n.t('welcomeWizardMSSList3Free')}</li>
          ) : (
            <li>{MailPoet.I18n.t('welcomeWizardMSSList3Paid')}</li>
          )}
        </List>
      </div>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <Button
        className="mailpoet-wizard-continue-button"
        isFullWidth
        href={mailpoetAccountUrl}
        target="_blank"
        rel="noopener noreferrer"
        onClick={(event) =>
          openMailPoetShopAndGoToTheNextPart(event, navigate, step)
        }
        iconEnd={<Icon icon={external} />}
      >
        {MailPoet.I18n.t('welcomeWizardMSSFirstPartButton')}
      </Button>

      <div className="mailpoet-gap" />
      <div className="mailpoet-gap" />

      <OwnEmailServiceNote />
    </>
  );
}

export { MSSStepFirstPart };
