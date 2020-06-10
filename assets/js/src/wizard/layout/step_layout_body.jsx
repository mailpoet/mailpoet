import PropTypes from 'prop-types';
import React from 'react';

const WelcomeWizardStepLayoutBody = (props) => (
  <div className="mailpoet_welcome_wizard_flex">
    <div className="mailpoet_welcome_wizard_illustration">
      <img src={props.illustrationUrl} alt="" />
    </div>
    <div className="mailpoet_welcome_wizard_step">
      {props.children}
    </div>
  </div>
);

WelcomeWizardStepLayoutBody.propTypes = {
  illustrationUrl: PropTypes.string.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

export default WelcomeWizardStepLayoutBody;
