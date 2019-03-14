import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const WelcomeWizardWooCommerceStep = props => (
  <div className="mailpoet_welcome_wizard_step_content">
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
        type="button"
        className="button button-primary"
        onClick={props.next}
        disabled={props.loading}
      >
        {MailPoet.I18n.t('gotIt')}
      </button>
    </div>
  </div>
);

WelcomeWizardWooCommerceStep.propTypes = {
  next: PropTypes.func.isRequired,
  screenshot_src: PropTypes.string.isRequired,
  loading: PropTypes.bool.isRequired,
};

export default WelcomeWizardWooCommerceStep;
