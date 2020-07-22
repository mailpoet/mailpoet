import PropTypes from 'prop-types';
import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

import Button from '../../common/button/button';
import Heading from '../../common/typography/heading/heading';
import YesNo from '../../common/form/yesno/yesno';

const WizardWooCommerceStep = (props) => {
  const [allowed, setAllowed] = useState(null);
  const [importType, setImportType] = useState(null);
  const [error, setError] = useState(null);

  const submit = (event) => {
    event.preventDefault();
    if (importType === null) {
      setError('importType');
      return false;
    }
    if (allowed === null) {
      setError('allowed');
      return false;
    }
    props.submitForm(importType, allowed === 'true');
    setError(null);
    return false;
  };

  const finishButtonText = props.isWizardStep ? MailPoet.I18n.t('wooCommerceSetupFinishButtonTextWizard')
    : MailPoet.I18n.t('wooCommerceSetupFinishButtonTextStandalone');

  return (
    <>
      <Heading level={1}>{MailPoet.I18n.t('wooCommerceSetupTitle')}</Heading>

      <div className="mailpoet-gap" />
      <p>{MailPoet.I18n.t('wooCommerceSetupInfo')}</p>
      <div className="mailpoet-gap" />

      <form onSubmit={submit}>
        <div className="mailpoet_wizard_woocommerce_option">
          <div className="mailpoet_wizard_woocommerce_toggle">
            <YesNo
              showError={error === 'importType'}
              onCheck={(value) => setImportType(value ? 'subscribed' : 'unsubscribed')}
              name="mailpoet_woocommerce_import_type"
            />
          </div>
          <p>
            {ReactStringReplace(
              MailPoet.I18n.t('wooCommerceSetupImportInfo'),
              /\[link\](.*?)\[\/link\]/,
              (match) => (
                <a
                  key={match}
                  href="https://kb.mailpoet.com/article/284-import-old-customers-to-the-woocommerce-customers-list"
                  data-beacon-article="5d722c7104286364bc8ecf19"
                  rel="noopener noreferrer"
                  target="_blank"
                >
                  {match}
                </a>
              )
            )}
          </p>
          <div className="mailpoet_wizard_gdpr">
            <span>GDPR</span>
            {MailPoet.I18n.t('wooCommerceSetupImportGDPRInfo')}
          </div>
        </div>

        <div className="mailpoet_wizard_woocommerce_option">
          <div className="mailpoet_wizard_woocommerce_toggle">
            <YesNo
              showError={error === 'allowed'}
              onCheck={(value) => setAllowed(value)}
              name="mailpoet_woocommerce_tracking"
            />
          </div>
          <p>
            {ReactStringReplace(
              MailPoet.I18n.t('wooCommerceSetupTrackingInfo'),
              /\[link\](.*?)\[\/link\]/,
              (match) => (
                <a
                  key={match}
                  href="https://kb.mailpoet.com/article/280-woocommerce-cookie-tracking"
                  data-beacon-article="5d5fa44c2c7d3a7a4d778906"
                  rel="noopener noreferrer"
                  target="_blank"
                >
                  {match}
                </a>
              )
            )}
          </p>
          <div className="mailpoet_wizard_gdpr">
            <span>GDPR</span>
            {MailPoet.I18n.t('wooCommerceSetupImportGDPRInfo')}
          </div>
        </div>

        <div className="mailpoet-gap" />
        <Button
          isFullWidth
          type="submit"
          withSpinner={props.loading}
          disabled={props.loading}
        >
          {finishButtonText}
        </Button>
      </form>
    </>
  );
};

WizardWooCommerceStep.propTypes = {
  submitForm: PropTypes.func.isRequired,
  loading: PropTypes.bool.isRequired,
  isWizardStep: PropTypes.bool,
};

WizardWooCommerceStep.defaultProps = {
  isWizardStep: false,
};

export default WizardWooCommerceStep;
