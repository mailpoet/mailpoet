import { __, _x } from '@wordpress/i18n';
import { useState } from 'react';
import ReactStringReplace from 'react-string-replace';

import { Button, TypographyHeading } from '../../common';
import { YesNo } from '../../common/form/yesno/yesno';

type WizardWooCommerceStepPropType = {
  submitForm: (string, boolean) => void;
  loading: boolean;
  showCustomersImportSetting: boolean;
  isWizardStep: boolean;
};

function WizardWooCommerceStep({
  submitForm,
  loading,
  showCustomersImportSetting,
  isWizardStep = false,
}: WizardWooCommerceStepPropType): JSX.Element {
  const [allowed, setAllowed] = useState(null);
  const [importType, setImportType] = useState(
    showCustomersImportSetting === false ? 'unsubscribed' : null,
  );
  const [submitted, setSubmitted] = useState(false);

  const submit = (event) => {
    event.preventDefault();
    setSubmitted(true);
    if (importType === null || allowed === null) {
      return false;
    }
    submitForm(importType, allowed);
    return false;
  };

  const buttonText = isWizardStep
    ? _x('Continue', 'A label on a button', 'mailpoet')
    : _x(
        'Start using WooCommerce features',
        'Submit button caption on the standalone WooCommerce setup page',
        'mailpoet',
      );

  let importTypeChecked;
  if (importType === 'subscribed') importTypeChecked = true;
  if (importType === 'unsubscribed') importTypeChecked = false;

  return (
    <>
      <TypographyHeading level={1}>
        {_x(
          'Power up your WooCommerce store',
          'Title on the WooCommerce setup page',
          'mailpoet',
        )}
      </TypographyHeading>

      <div className="mailpoet-gap" />
      <p>
        {__(
          'MailPoet comes with powerful features for WooCommerce. Select features that you would like to use with your store.',
          'mailpoet',
        )}
      </p>
      <div className="mailpoet-gap" />
      <form onSubmit={submit}>
        <div>
          {showCustomersImportSetting ? (
            <div className="mailpoet-wizard-woocommerce-option">
              <div className="mailpoet-wizard-woocommerce-toggle">
                <YesNo
                  showError={submitted && importType === null}
                  checked={importTypeChecked}
                  onCheck={(value) =>
                    setImportType(value ? 'subscribed' : 'unsubscribed')
                  }
                  name="mailpoet_woocommerce_import_type"
                  automationId="woocommerce_import_type"
                />
              </div>
              <div>
                <p>
                  {ReactStringReplace(
                    __(
                      'Do you want to import your WooCommerce customers as subscribed? [link]Learn more[/link].',
                      'mailpoet',
                    ),
                    /\[link\](.*?)\[\/link\]/,
                    (match) => (
                      <a
                        key={match}
                        href="https://kb.mailpoet.com/article/284-import-old-customers-to-the-woocommerce-customers-list"
                        rel="noopener noreferrer"
                        target="_blank"
                      >
                        {match}
                      </a>
                    ),
                  )}
                </p>
                <div className="mailpoet-wizard-note">
                  <span>GDPR</span>
                  {_x(
                    'To be compliant with privacy regulations, your customers must have explicitly accepted to receive your marketing emails.',
                    'GDPR compliance information',
                    'mailpoet',
                  )}
                </div>
              </div>
            </div>
          ) : null}
          <div className="mailpoet-wizard-woocommerce-option">
            <div className="mailpoet-wizard-woocommerce-toggle">
              <YesNo
                showError={submitted && allowed === null}
                checked={allowed}
                onCheck={(value) => setAllowed(value)}
                name="mailpoet_woocommerce_tracking"
                automationId="woocommerce_tracking"
              />
            </div>
            <div>
              <p>
                {ReactStringReplace(
                  __(
                    'Collect more precise email and site engagement, and e-commerce metrics by enabling cookie tracking. [link]Learn more[/link].',
                    'mailpoet',
                  ),
                  /\[link\](.*?)\[\/link\]/,
                  (match) => (
                    <a
                      key={match}
                      href="https://kb.mailpoet.com/article/280-woocommerce-cookie-tracking"
                      rel="noopener noreferrer"
                      target="_blank"
                    >
                      {match}
                    </a>
                  ),
                )}
              </p>
              <div className="mailpoet-wizard-note">
                <span>GDPR</span>
                {_x(
                  'To be compliant, you should display a cookie tracking banner on your website.',
                  'GDPR compliance information',
                  'mailpoet',
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="mailpoet-gap" />
        <Button
          className="mailpoet-wizard-continue-button"
          isFullWidth
          type="submit"
          withSpinner={loading}
          disabled={loading}
          automationId="submit_woocommerce_setup"
        >
          {buttonText}
        </Button>
      </form>
    </>
  );
}

export { WizardWooCommerceStep };
