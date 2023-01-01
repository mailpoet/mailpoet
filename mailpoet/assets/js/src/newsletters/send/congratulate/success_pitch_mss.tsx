import { __ } from '@wordpress/i18n';
import { useState } from 'react';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

import { WelcomeWizardStepLayoutBody } from 'wizard/layout/step_layout_body.jsx';
import { Button, Heading, List } from 'common';

function FreeBenefitsList(): JSX.Element {
  return (
    <List>
      <li>{MailPoet.I18n.t('congratulationsMSSPitchList1')}</li>
      <li>{MailPoet.I18n.t('congratulationsMSSPitchList2')}</li>
      <li>{MailPoet.I18n.t('congratulationsMSSPitchList3')}</li>
      <li>{MailPoet.I18n.t('congratulationsMSSPitchList4')}</li>
    </List>
  );
}

type Props = {
  MSSPitchIllustrationUrl: string;
  onFinish: () => void;
  subscribersCount: number;
  purchaseUrl: string;
  newsletter: {
    status: string;
    type: string;
  };
};

function getHeader(newsletterType: string): string {
  const typeMap = {
    standard: __('Your email has been sent!', 'mailpoet'),
    welcome: __('You are all set up and ready to go!', 'mailpoet'),
    notification: __('You are all set up and ready to go!', 'mailpoet'),
    woocommerce: __('You are all set up and ready to go!', 'mailpoet'),
  };

  return (
    typeMap[newsletterType] ||
    __('You are all set up and ready to go!', 'mailpoet')
  );
}

export function PitchMss(props: Props): JSX.Element {
  const [isClosing, setIsClosing] = useState(false);
  const next = (): void => {
    props.onFinish();
    setIsClosing(true);
  };

  return (
    <>
      <Heading level={1}>{getHeader(props.newsletter.type)}</Heading>
      <WelcomeWizardStepLayoutBody
        illustrationUrl={props.MSSPitchIllustrationUrl}
      >
        <div className="mailpoet-welcome-wizard-step-content">
          <Heading level={4}>
            {__(
              'What’s next? Sign up to the MailPoet Starter plan for fast and reliable email delivery',
              'mailpoet',
            )}
          </Heading>
          <p>
            {MailPoet.I18n.t(
              props.subscribersCount < 1000
                ? 'congratulationsMSSPitchFreeSubtitle'
                : 'congratulationsMSSPitchNotFreeSubtitle',
            )}
          </p>
          <Heading level={5}>
            {MailPoet.I18n.t('congratulationsMSSPitchFreeListTitle')}:
          </Heading>
          <FreeBenefitsList />

          <p>
            {ReactStringReplace(
              MailPoet.I18n.t('congratulationsMSSEnterYourKey'),
              /\[link\](.*?)\[\/link\]/g,
              (match, i) => (
                <a href="admin.php?page=mailpoet-settings#/premium" key={i}>
                  {match}
                </a>
              ),
            )}
          </p>
          <div className="mailpoet-gap" />
          <div className="mailpoet-gap" />

          <Button
            isFullWidth
            href={props.purchaseUrl}
            target="_blank"
            rel="noopener noreferrer"
            onClick={(event) => {
              event.preventDefault();
              window.open(props.purchaseUrl);
              next();
            }}
          >
            {MailPoet.I18n.t('congratulationsMSSPitchFreeButton')}
          </Button>
          <Button
            isFullWidth
            variant="tertiary"
            onClick={next}
            onKeyDown={(event) => {
              if (
                ['keydown', 'keypress'].includes(event.type) &&
                ['Enter', ' '].includes(event.key)
              ) {
                event.preventDefault();
                next();
              }
            }}
            withSpinner={isClosing}
          >
            {MailPoet.I18n.t('congratulationsMSSPitchNoThanks')}
          </Button>
        </div>
      </WelcomeWizardStepLayoutBody>
    </>
  );
}
