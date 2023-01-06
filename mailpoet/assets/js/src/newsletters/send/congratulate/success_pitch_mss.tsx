import { useState } from 'react';
import { MailPoet } from 'mailpoet';

import { Heading } from 'common/typography/heading/heading';
import { WelcomeWizardStepLayoutBody } from '../../../wizard/layout/step_layout_body.jsx';
import {
  Controls,
  FreeBenefitsList,
} from '../../../wizard/steps/pitch_mss_step';

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
    standard: MailPoet.I18n.t('congratulationsMSSPitchHeader'),
    welcome: MailPoet.I18n.t('congratulationsMSSPitchHeaderAutomated'),
    notification: MailPoet.I18n.t('congratulationsMSSPitchHeaderAutomated'),
    woocommerce: MailPoet.I18n.t('congratulationsMSSPitchHeaderAutomated'),
  };

  return (
    typeMap[newsletterType] ||
    MailPoet.I18n.t('congratulationsMSSPitchHeaderAutomated')
  );
}

export function PitchMss(props: Props): JSX.Element {
  const [isClosing, setIsClosing] = useState(false);
  return (
    <>
      <Heading level={1}>{getHeader(props.newsletter.type)}</Heading>
      <WelcomeWizardStepLayoutBody
        illustrationUrl={props.MSSPitchIllustrationUrl}
      >
        <div className="mailpoet-welcome-wizard-step-content">
          <Heading level={4}>
            {MailPoet.I18n.t('congratulationsMSSPitchSubHeader')}
          </Heading>
          <p>
            {MailPoet.I18n.t(
              props.subscribersCount < 1000
                ? 'welcomeWizardMSSFreeSubtitle'
                : 'welcomeWizardMSSNotFreeSubtitle',
            )}
          </p>
          <Heading level={5}>
            {MailPoet.I18n.t('welcomeWizardMSSFreeListTitle')}:
          </Heading>
          <FreeBenefitsList />
          <Controls
            mailpoetAccountUrl={props.purchaseUrl}
            next={(): void => {
              props.onFinish();
              setIsClosing(true);
            }}
            nextButtonText={MailPoet.I18n.t('welcomeWizardMSSFreeButton')}
            nextWithSpinner={isClosing}
          />
        </div>
      </WelcomeWizardStepLayoutBody>
    </>
  );
}
