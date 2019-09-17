import PropTypes from 'prop-types';
import React from 'react';
import SteppedProgressBar from '../../common/stepped_progess_bar.jsx';

const WelcomeWizardStepLayoutBody = (props) => (
  <div className="mailpoet_welcome_wizard_flex">
    <div className="mailpoet_welcome_wizard_illustration">
      <img src={props.illustrationUrl} alt="" />
    </div>
    <div className="mailpoet_welcome_wizard_step">
      { props.step <= props.stepsCount
        ? (
          <SteppedProgressBar steps_count={props.stepsCount} step={props.step} />
        ) : null
      }
      {props.children}
    </div>
  </div>
);

WelcomeWizardStepLayoutBody.propTypes = {
  illustrationUrl: PropTypes.string.isRequired,
  step: PropTypes.number.isRequired,
  stepsCount: PropTypes.number.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

export default WelcomeWizardStepLayoutBody;
