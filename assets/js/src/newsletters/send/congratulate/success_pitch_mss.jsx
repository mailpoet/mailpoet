import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import WelcomeWizardStepLayoutBody from '../../../wizard/layout/step_layout_body.jsx';
import WelcomeWizardPitchMSSStep from '../../../wizard/steps/pitch_mss_step.jsx';

function PitchMss(props) {
  return (
    <div className="mailpoet_congratulate_success">
      <h1>{MailPoet.I18n.t('congratulationsMSSPitchHeader')}</h1>
      <WelcomeWizardStepLayoutBody
        illustrationUrl={props.MSSPitchIllustrationUrl}
        displayProgressBar={false}
      >
        <WelcomeWizardPitchMSSStep
          next={props.onFinish}
          subscribersCount={25}
          mailpoetAccountUrl="http://xxx"
          isWoocommerceActive={true}
        />
      </WelcomeWizardStepLayoutBody>
    </div>
  );
}

PitchMss.propTypes = {
  MSSPitchIllustrationUrl: PropTypes.string.isRequired,
  onFinish: PropTypes.func.isRequired,
};


export default PitchMss;
