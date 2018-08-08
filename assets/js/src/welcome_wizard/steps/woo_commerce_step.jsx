import React from 'react';
import MailPoet from 'mailpoet';

const WelcomeWizardWooCommerceStep = props => (
  <div className="mailpoet_welcome_wizard_step_content mailpoet_welcome_wizard_centered_column">
    <h1>{MailPoet.I18n.t('welcomeWizardWooCommerceStepTitle')}</h1>
    <p>
      {MailPoet.I18n.t('welcomeWizardHelpingShopOwnersText')}
    </p>
    <p>
      {MailPoet.I18n.t('welcomeWizardWooCommerceEmailsText')}
    </p>
    <img
      src={props.screenshot_src}
      className="mailpoet_welcome_wizard_woo_screenshot"
      alt="WooCommerce email"
    />
    <div
      className={
        `mailpoet_welcome_wizard_step_controls
        ${(props.loading ? 'mailpoet_welcome_wizard_step_controls_loading' : '')}`
      }
    >
      <button
        className="button button-primary"
        onClick={props.next}
        disabled={props.loading}
      >
        {MailPoet.I18n.t('gotIt')}
      </button>
    </div>
  </div>
);

module.exports = WelcomeWizardWooCommerceStep;

WelcomeWizardWooCommerceStep.propTypes = {
  next: React.PropTypes.func.isRequired,
  screenshot_src: React.PropTypes.string.isRequired,
  loading: React.PropTypes.bool.isRequired,
};
