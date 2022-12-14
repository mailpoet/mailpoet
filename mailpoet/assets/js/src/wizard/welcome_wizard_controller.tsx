import { useCallback, useEffect, useState } from 'react';
import { partial } from 'underscore';

import { MailPoet } from 'mailpoet';
import { WelcomeWizardSenderStep } from './steps/sender_step';
import { WelcomeWizardUsageTrackingStep } from './steps/usage_tracking_step.jsx';
import { WelcomeWizardPitchMSSStep } from './steps/pitch_mss_step.jsx';
import { WooCommerceController } from './woocommerce_controller';
import { WelcomeWizardStepLayout } from './layout/step_layout.jsx';

import { createSenderSettings } from './create_sender_settings.jsx';
import {
  getStepsCount,
  mapStepNumberToStepName,
  redirectToNextStep,
} from './steps_numbers.jsx';
import { Steps } from '../common/steps/steps';
import { StepsContent } from '../common/steps/steps_content';
import { TopBar } from '../common/top_bar/top_bar';
import { ErrorBoundary } from '../common';

type WelcomeWizardStepsControllerPropType = {
  match: { params: { step: string } };
  history: { push: (string) => void };
};

function WelcomeWizardStepsController({
  match,
  history,
}: WelcomeWizardStepsControllerPropType): JSX.Element {
  const stepsCount = getStepsCount();
  const step = parseInt(match.params.step, 10);

  const [loading, setLoading] = useState(false);
  const [sender, setSender] = useState(window.sender_data);

  useEffect(() => {
    if (step > stepsCount || step < 1) {
      history.push('/steps/1');
    }
  }, [step, stepsCount, history]);

  function updateSettings(data) {
    setLoading(true);
    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data,
    })
      .then(() => setLoading(false))
      .fail((response: ErrorResponse) => {
        setLoading(false);
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true },
          );
        }
      });
  }

  function finishWizard() {
    void updateSettings({
      version: window.mailpoet_version,
    }).then(() => {
      window.location.href = window.finish_wizard_url;
    });
  }

  const redirect = partial(redirectToNextStep, history, finishWizard);

  const submitTracking = useCallback(
    (tracking, libs3rdParty) => {
      setLoading(true);
      void updateSettings({
        analytics: { enabled: tracking ? '1' : '' },
        '3rd_party_libs': { enabled: libs3rdParty ? '1' : '' },
      }).then(() => redirect(step));
    },
    [redirect, step],
  );

  const updateSender = useCallback(
    (data: { address: string }) => {
      setSender({ ...sender, ...data });
    },
    [sender],
  );

  const submitSender = useCallback(() => {
    void updateSettings(createSenderSettings(sender)).then(() =>
      redirect(step),
    );
  }, [redirect, sender, step]);

  const skipSenderStep = useCallback(
    (e) => {
      e.preventDefault();
      setLoading(true);
      void updateSettings(
        createSenderSettings({ address: window.admin_email, name: '' }),
      ).then(() => {
        redirect(step);
      });
    },
    [redirect, step],
  );

  const stepName = mapStepNumberToStepName(step);

  return (
    <>
      <TopBar logoWithLink={false}>
        <Steps count={stepsCount} current={step} />
      </TopBar>
      <StepsContent>
        {stepName === 'WelcomeWizardSenderStep' ? (
          <WelcomeWizardStepLayout
            illustrationUrl={window.wizard_sender_illustration_url}
          >
            <ErrorBoundary>
              <WelcomeWizardSenderStep
                update_sender={updateSender}
                submit_sender={submitSender}
                skipStep={skipSenderStep}
                loading={loading}
                sender={sender}
              />
            </ErrorBoundary>
          </WelcomeWizardStepLayout>
        ) : null}

        {stepName === 'WelcomeWizardUsageTrackingStep' ? (
          <WelcomeWizardStepLayout
            illustrationUrl={window.wizard_tracking_illustration_url}
          >
            <ErrorBoundary>
              <WelcomeWizardUsageTrackingStep
                loading={loading}
                submitForm={submitTracking}
              />
            </ErrorBoundary>
          </WelcomeWizardStepLayout>
        ) : null}

        {stepName === 'WelcomeWizardPitchMSSStep' ? (
          <WelcomeWizardStepLayout
            illustrationUrl={window.wizard_MSS_pitch_illustration_url}
          >
            <ErrorBoundary>
              <WelcomeWizardPitchMSSStep
                next={() => redirect(step)}
                subscribersCount={window.mailpoet_subscribers_count}
                mailpoetAccountUrl={window.mailpoet_account_url}
                purchaseUrl={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
                  MailPoet.subscribersCount,
                  MailPoet.currentWpUserEmail,
                  'business',
                  { utm_medium: 'onboarding', utm_campaign: 'purchase' },
                )}
              />
            </ErrorBoundary>
          </WelcomeWizardStepLayout>
        ) : null}

        {stepName === 'WizardWooCommerceStep' ? (
          <ErrorBoundary>
            <WooCommerceController
              isWizardStep
              redirectToNextStep={() => redirect(step)}
            />
          </ErrorBoundary>
        ) : null}
      </StepsContent>
    </>
  );
}

WelcomeWizardStepsController.displayName = 'WelcomeWizardStepsController';

export { WelcomeWizardStepsController };
