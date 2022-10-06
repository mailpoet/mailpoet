import PropTypes from 'prop-types';
import { useState } from 'react';
import { MailPoet } from 'mailpoet';
import { StepsContent } from 'common/steps/steps_content.tsx';
import { WizardWooCommerceStep } from './steps/woo_commerce_step.jsx';
import { WelcomeWizardStepLayout } from './layout/step_layout.jsx';

function WooCommerceController({ isWizardStep = false }) {
  const [loading, setLoading] = useState(false);

  const handleApiError = (response) => {
    setLoading(false);
    MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
  };

  const updateSettings = (data) =>
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data,
    }).fail(handleApiError);

  const scheduleImport = () =>
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'importExport',
      action: 'setupWooCommerceInitialImport',
    }).fail(handleApiError);

  const finishWizard = () => {
    window.location = window.finish_wizard_url;
  };

  const submit = (importType, allowed) => {
    setLoading(true);
    const trackingLevelForDisabledCookies =
      MailPoet.trackingConfig.level === 'basic' ? 'basic' : 'partial';
    const newTrackingLevel = allowed ? 'full' : trackingLevelForDisabledCookies;
    const settings = {
      // importType
      woocommerce_import_screen_displayed: 1,
      'mailpoet_subscribe_old_woocommerce_customers.enabled':
        importType === 'subscribed' ? 1 : 0,
      // cookies allowed
      'tracking.level': newTrackingLevel,
      'woocommerce.accept_cookie_revenue_tracking.set': 1,
    };
    updateSettings(settings).then(scheduleImport).then(finishWizard);
  };

  const result = (
    <WelcomeWizardStepLayout
      illustrationUrl={window.wizard_woocommerce_illustration_url}
    >
      <WizardWooCommerceStep
        loading={loading}
        submitForm={submit}
        isWizardStep={isWizardStep}
        showCustomersImportSetting={window.mailpoet_show_customers_import}
      />
    </WelcomeWizardStepLayout>
  );

  if (!isWizardStep) {
    return <StepsContent>{result}</StepsContent>;
  }

  return result;
}

WooCommerceController.propTypes = {
  isWizardStep: PropTypes.bool,
};

WooCommerceController.defaultProps = {
  isWizardStep: false,
};

export { WooCommerceController };
