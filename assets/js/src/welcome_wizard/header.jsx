import React from 'react';
import SteppedProgressBar from '../common/stepped_progess_bar.jsx';

const WelcomeWizardHeader = props => (
  <div className="mailpoet_welcome_wizard_centered_column mailpoet_welcome_wizard_header">
    <img src={props.logo_src} width="200" alt="MailPoet logo" />
    {
      props.current_step <= props.steps_count ?
        (<SteppedProgressBar steps_count={props.steps_count} step={props.current_step} />)
        : null
    }
  </div>
);

WelcomeWizardHeader.propTypes = {
  current_step: React.PropTypes.number.isRequired,
  steps_count: React.PropTypes.number.isRequired,
  logo_src: React.PropTypes.string.isRequired,
};

module.exports = WelcomeWizardHeader;
