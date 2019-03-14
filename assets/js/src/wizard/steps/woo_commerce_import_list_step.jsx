import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';

const WizardWooCommerceImportListStep = props => (
  <div className="mailpoet_welcome_wizard_step_content mailpoet_welcome_wizard_centered_column">
    <h1>{MailPoet.I18n.t('wooCommerceListImportTitle')}</h1>
  </div>
);

WizardWooCommerceImportListStep.propTypes = {};

export default WizardWooCommerceImportListStep;
