import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const WelcomeWizardUsageTrackingStep = (props) => (
  <div className="mailpoet_welcome_wizard_step_content">
    <h1>{MailPoet.I18n.t('welcomeWizardUsageTrackingStepTitle')}</h1>
    <p>{MailPoet.I18n.t('welcomeWizardTrackingText')}</p>
    <h2 className="welcome_wizard_tracking_sub_title">{MailPoet.I18n.t('welcomeWizardUsageTrackingStepSubTitle')}</h2>
    <ul className="welcome_wizard_tracking_list">
      <li>{MailPoet.I18n.t('welcomeWizardTrackingList1')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardTrackingList2')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardTrackingList3')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardTrackingList4')}</li>
      <li>{MailPoet.I18n.t('welcomeWizardTrackingList5')}</li>
    </ul>
    <a
      href=" https://kb.mailpoet.com/article/130-sharing-your-data-with-us"
      data-beacon-article="57ce0aaac6979108399a0454"
      target="_blank"
      rel="noopener noreferrer"
    >
      {MailPoet.I18n.t('welcomeWizardTrackingLink')}
    </a>
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
