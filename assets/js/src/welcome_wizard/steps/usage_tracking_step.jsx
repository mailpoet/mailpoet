import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

const WelcomeWizardUsageTrackingStep = props => (
  <div className="mailpoet_welcome_wizard_step_content mailpoet_welcome_wizard_centered_column">
    <h1>{MailPoet.I18n.t('welcomeWizardUsageTrackingStepTitle')}</h1>
    <p>
      {
        ReactStringReplace(
          MailPoet.I18n.t('welcomeWizardTrackingText'),
          /\[link\](.*?)\[\/link\]/g,
          match => (
            <a
              key="docs_link"
              href="https://beta.docs.mailpoet.com/article/130-sharing-your-data-with-us"
              target="_blank"
              rel="noopener noreferrer"
            >
              { match }
            </a>
          )
        )
      }
    </p>
    <div
      className={
        `mailpoet_welcome_wizard_step_controls
        ${(props.loading ? 'mailpoet_welcome_wizard_step_controls_loading' : '')}`
      }
    >
      <button
        type="button"
        className="button"
        onClick={props.skip_action}
        disabled={props.loading}
      >
        {MailPoet.I18n.t('skip')}
      </button>
      <button
        type="button"
        className="button button-primary"
        onClick={props.allow_action}
        disabled={props.loading}
      >
        {props.allow_text}
      </button>
    </div>
  </div>
);

WelcomeWizardUsageTrackingStep.propTypes = {
  allow_action: PropTypes.func.isRequired,
  allow_text: PropTypes.string.isRequired,
  skip_action: PropTypes.func.isRequired,
  loading: PropTypes.bool.isRequired,
};

export default WelcomeWizardUsageTrackingStep;
