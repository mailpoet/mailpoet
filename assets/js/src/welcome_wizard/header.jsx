import PropTypes from 'prop-types';
import React from 'react';
import SteppedProgressBar from '../common/stepped_progess_bar.jsx';

const WelcomeWizardHeader = props => (
  <div className="mailpoet_welcome_wizard_centered_column mailpoet_welcome_wizard_header">
    <img src={props.logo_src} width="200" height="87" alt="MailPoet logo" />
    {
      props.current_step <= props.steps_count
        ? (<SteppedProgressBar steps_count={props.steps_count} step={props.current_step} />)
        : null
    }
  </div>
);

WelcomeWizardHeader.propTypes = {
  current_step: PropTypes.number.isRequired,
  steps_count: PropTypes.number.isRequired,
  logo_src: PropTypes.string.isRequired,
};

export default WelcomeWizardHeader;
