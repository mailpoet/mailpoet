import PropTypes from 'prop-types';
import React from 'react';
import SteppedProgressBar from '../../common/stepped_progess_bar.jsx';

const WelcomeWizardStepLayoutBody = (props) => (
  <div className="mailpoet_welcome_wizard_flex">
    <div className="mailpoet_welcome_wizard_illustration">
      <img src={props.illustrationUrl} alt="" />
    </div>
    <div className="mailpoet_welcome_wizard_step">
      { props.displayProgressBar && (props.step <= props.stepsCount)
        ? (
          <SteppedProgressBar steps_count={props.stepsCount} step={props.step} />
        ) : null}
      {props.children}
    </div>
  </div>
);

WelcomeWizardStepLayoutBody.propTypes = {
  illustrationUrl: PropTypes.string.isRequired,
  displayProgressBar: PropTypes.bool,
  step: PropTypes.number,
  stepsCount: PropTypes.number,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

WelcomeWizardStepLayoutBody.defaultProps = {
  displayProgressBar: true,
  step: 0,
  stepsCount: -1,
};

export default WelcomeWizardStepLayoutBody;
