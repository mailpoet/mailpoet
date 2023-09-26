import { useState } from 'react';
import { MailPoet } from 'mailpoet';
import { useSetting } from 'settings/store/hooks';
import { Settings } from 'settings/store/types';
import { StepsContent } from 'common/steps/steps_content';
import { WizardWooCommerceStep } from './steps/woocommerce_step';
import { WelcomeWizardStepLayout } from './layout/step_layout.jsx';
import { ErrorBoundary } from '../common';

type WooCommerceControllerPropType = {
  isWizardStep: boolean;
  redirectToNextStep: () => void;
};

function WooCommerceController({
  isWizardStep = false,
  redirectToNextStep = null,
}: WooCommerceControllerPropType): JSX.Element {
  const [loading, setLoading] = useState(false);
  const [woocommerce, setWoocommerce] = useSetting('woocommerce');
  const setTracking = useSetting('tracking')[1];
  const setImportScreenDisplayed = useSetting(
    'woocommerce_import_screen_displayed',
  )[1];
  const setSubsrcibeOldCustomers = useSetting(
    'mailpoet_subscribe_old_woocommerce_customers',
  )[1];

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

  const finishWizard = async () => {
    if (isWizardStep) {
      await updateSettings({
        version: window.mailpoet_version,
      }).then(() => {
        window.location.href = window.finish_wizard_url;
      });
    } else {
      window.location.href = window.finish_wizard_url;
    }
  };

  const submit = async (importType, allowed) => {
    setLoading(true);
    const trackingLevelForDisabledCookies =
      MailPoet.trackingConfig.level === 'basic' ? 'basic' : 'partial';
    const trackingData: Settings['tracking'] = {
      level: allowed ? 'full' : trackingLevelForDisabledCookies,
    };
    const subscribeOldCustomersData: Settings['mailpoet_subscribe_old_woocommerce_customers'] =
      {
        enabled: importType === 'subscribed' ? '1' : '',
      };
    const settings = {
      // importType
      woocommerce_import_screen_displayed: 1,
      'mailpoet_subscribe_old_woocommerce_customers.enabled':
        subscribeOldCustomersData.enabled,
      // cookies allowed
      'tracking.level': trackingData.level,
      'woocommerce.accept_cookie_revenue_tracking.set': '1',
    };
    await updateSettings(settings);
    setTracking(trackingData);
    setSubsrcibeOldCustomers(subscribeOldCustomersData);
    setWoocommerce({
      ...woocommerce,
      accept_cookie_revenue_tracking: {
        ...(woocommerce.accept_cookie_revenue_tracking || {}),
        set: '1',
      },
    });
    setImportScreenDisplayed('1');
    await scheduleImport();
    if (isWizardStep) {
      redirectToNextStep();
    } else {
      await finishWizard();
    }
  };

  const result = (
    <WelcomeWizardStepLayout
      illustrationUrl={window.wizard_woocommerce_illustration_url}
    >
      <ErrorBoundary>
        <WizardWooCommerceStep
          loading={loading}
          submitForm={submit}
          isWizardStep={isWizardStep}
          showCustomersImportSetting={window.mailpoet_show_customers_import}
        />
      </ErrorBoundary>
    </WelcomeWizardStepLayout>
  );

  if (!isWizardStep) {
    return <StepsContent>{result}</StepsContent>;
  }

  return result;
}

WooCommerceController.displayName = 'WooCommerceController';
export { WooCommerceController };
