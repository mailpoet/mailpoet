import PropTypes from 'prop-types';
import React from 'react';

import WelcomeWizardStepLayoutBody from './step_layout_body.jsx';

const WelcomeWizardStepLayout = (props) => (
  <>
    <div className="mailpoet_welcome_wizard_header">
      <img src={window.mailpoet_logo_url} width="200" height="87" alt="MailPoet logo" />
    </div>
    <WelcomeWizardStepLayoutBody
      illustrationUrl={props.illustrationUrl}
      step={props.step}
      stepsCount={props.stepsCount}
      displayProgressBar={props.displayProgressBar}
    >
      {props.children}
    </WelcomeWizardStepLayoutBody>
  </>
);

WelcomeWizardStepLayout.propTypes = {
  illustrationUrl: PropTypes.string.isRequired,
  step: PropTypes.number.isRequired,
  displayProgressBar: PropTypes.bool,
  stepsCount: PropTypes.number.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

WelcomeWizardStepLayout.defaultProps = {
  displayProgressBar: true,
};

export default WelcomeWizardStepLayout;
