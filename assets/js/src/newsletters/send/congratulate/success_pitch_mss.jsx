import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import Heading from 'common/typography/heading/heading';
import WelcomeWizardStepLayoutBody from '../../../wizard/layout/step_layout_body.jsx';
import { BenefitsList, Controls } from '../../../wizard/steps/pitch_mss_step.jsx';

function PitchMss(props) {
  return (
    <div className="mailpoet_congratulate_success mailpoet_congratulate_mss_pitch">
      <Heading level={1}>{MailPoet.I18n.t('congratulationsMSSPitchHeader')}</Heading>
      <WelcomeWizardStepLayoutBody
        illustrationUrl={props.MSSPitchIllustrationUrl}
      >
        <div className="mailpoet-welcome-wizard-step-content">
          <Heading level={4}>{MailPoet.I18n.t('congratulationsMSSPitchSubHeader')}</Heading>
          <p>
            {
            MailPoet.I18n.t(props.subscribersCount < 1000
              ? 'welcomeWizardMSSFreeSubtitle'
              : 'welcomeWizardMSSNotFreeSubtitle')
          }
          </p>
          <Heading level={5}>
            {MailPoet.I18n.t('welcomeWizardMSSFreeListTitle')}
            :
          </Heading>
          <BenefitsList />
          <Controls
            mailpoetAccountUrl={props.mailpoetAccountUrl}
            next={props.onFinish}
            nextButtonText={MailPoet.I18n.t('welcomeWizardMSSFreeButton')}
          />
        </div>
      </WelcomeWizardStepLayoutBody>
    </div>
  );
}

PitchMss.propTypes = {
  MSSPitchIllustrationUrl: PropTypes.string.isRequired,
  onFinish: PropTypes.func.isRequired,
  subscribersCount: PropTypes.number.isRequired,
  mailpoetAccountUrl: PropTypes.string.isRequired,
};


export default PitchMss;
