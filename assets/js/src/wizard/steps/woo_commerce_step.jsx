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
  const [submitted, setSubmitted] = useState(false);

  const submit = (event) => {
    event.preventDefault();
    setSubmitted(true);
    if (importType === null || allowed === null) {
      return false;
    }
    props.submitForm(importType, allowed === 'true');
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
        <div className="mailpoet-wizard-woocommerce-option">
          <div className="mailpoet-wizard-woocommerce-toggle">
            <YesNo
              showError={submitted && importType === null}
              onCheck={(value) => setImportType(value ? 'subscribed' : 'unsubscribed')}
              name="mailpoet_woocommerce_import_type"
              automationId="woocommerce_import_type"
            />
          </div>
          <div>
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
            <div className="mailpoet-wizard-gdpr">
              <span>GDPR</span>
              {MailPoet.I18n.t('wooCommerceSetupImportGDPRInfo')}
            </div>
          </div>
        </div>

        <div className="mailpoet-wizard-woocommerce-option">
          <div className="mailpoet-wizard-woocommerce-toggle">
            <YesNo
              showError={submitted && allowed === null}
              onCheck={(value) => setAllowed(value)}
              name="mailpoet_woocommerce_tracking"
              automationId="woocommerce_tracking"
            />
          </div>
          <div>
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
            <div className="mailpoet-wizard-gdpr">
              <span>GDPR</span>
              {MailPoet.I18n.t('wooCommerceSetupTrackingGDPRInfo')}
            </div>
          </div>
        </div>

        <div className="mailpoet-gap" />
        <Button
          isFullWidth
          type="submit"
          withSpinner={props.loading}
          disabled={props.loading}
          automationId="submit_woocommerce_setup"
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
