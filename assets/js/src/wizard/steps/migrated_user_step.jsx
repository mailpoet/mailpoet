import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const WelcomeWizardMigratedUserStep = props => (
  <div className="mailpoet_welcome_wizard_step_content">
    <h1>{MailPoet.I18n.t('welcomeWizardLetsStartTitle')}</h1>
    <p>{MailPoet.I18n.t('welcomeWizardSenderMigratedUserText')}</p>
    <div className="mailpoet_welcome_wizard_step_controls">
      <button type="button" className="button button-primary" onClick={props.next}>{MailPoet.I18n.t('next')}</button>
    </div>
  </div>
);

WelcomeWizardMigratedUserStep.propTypes = {
  next: PropTypes.func.isRequired,
};

export default WelcomeWizardMigratedUserStep;
