import { useCallback, useEffect, useState } from 'react';
import { useSetting } from 'settings/store/hooks';
import { Settings } from 'settings/store/types';
import { partial } from 'underscore';

import { History } from 'history';
import { WelcomeWizardSenderStep } from './steps/sender-step';
import { WelcomeWizardUsageTrackingStep } from './steps/usage-tracking-step';
import { WelcomeWizardPitchMSSStep } from './steps/pitch-mss-step';
import { WooCommerceController } from './woocommerce-controller';
import { WelcomeWizardStepLayout } from './layout/step-layout.jsx';

import { createSenderSettings } from './create-sender-settings.jsx';
import {
  getStepsCount,
  mapStepNumberToStepName, navigateToPath,
  redirectToNextStep,
} from './steps-numbers';
import { Steps } from '../common/steps/steps';
import { StepsContent } from '../common/steps/steps-content';
import { TopBar } from '../common/top-bar/top-bar';
import { ErrorBoundary } from '../common';
import { HideScreenOptions } from '../common/hide-screen-options/hide-screen-options';
import { finishWizard } from './finish-wizard';
import { updateSettings } from './update-settings';

type WelcomeWizardStepsControllerPropType = {
  match: { params: { step: string } };
  history: History;
};

function WelcomeWizardStepsController({
  match,
  history,
}: WelcomeWizardStepsControllerPropType): JSX.Element {
  const stepsCount = getStepsCount();
  const step = parseInt(match.params.step, 10);

  const [loading, setLoading] = useState(false);
  const [sender, setSender] = useSetting('sender');
  const setAnalytics = useSetting('analytics')[1];
  const setThirdPartyLibs = useSetting('3rd_party_libs')[1];

  useEffect(() => {
    if (step > stepsCount || step < 1) {
      void navigateToPath(history, '/steps/1');
    }
  }, [step, stepsCount, history]);

  const redirect = partial(redirectToNextStep, history, finishWizard);

  const updateTracking = useCallback(
    async (analytics: boolean, libs3rdParty: boolean) => {
      const analyticsData: Settings['analytics'] = {
        enabled: analytics ? '1' : '',
      };
      const thirdPartyLibsData: Settings['3rd_party_libs'] = {
        enabled: libs3rdParty ? '1' : '',
      };
      const updateData = {
        analytics: analyticsData,
        '3rd_party_libs': thirdPartyLibsData,
      };
      await updateSettings(updateData);
      setAnalytics(analyticsData);
      setThirdPartyLibs(thirdPartyLibsData);
    },
    [setAnalytics, setThirdPartyLibs],
  );

  const submitTracking = useCallback(
    async (tracking: boolean, libs3rdParty: boolean) => {
      setLoading(true);
      await updateTracking(tracking, libs3rdParty);
      redirect(step);
      setLoading(false);
    },
    [redirect, step, updateTracking],
  );

  const updateSender = useCallback(
    (data: { address: string }) => {
      setSender({ ...sender, ...data });
    },
    [sender, setSender],
  );

  const submitSender = useCallback(async () => {
    setLoading(true);
    if (window.mailpoet_is_dotcom && !window.wizard_has_tracking_settings) {
      await updateTracking(true, true);
    }
    await updateSettings(createSenderSettings(sender)).then(() =>
      redirect(step),
    );
    setLoading(false);
  }, [redirect, sender, step, updateTracking]);

  const skipSenderStep = useCallback(
    async (e) => {
      e.preventDefault();
      setLoading(true);
      const defaultSenderInfo = { address: window.admin_email, name: '' };

      if (window.mailpoet_is_dotcom && !window.wizard_has_tracking_settings) {
        await updateTracking(true, true);
      }
      await updateSettings(createSenderSettings(defaultSenderInfo)).then(() => {
        setSender(defaultSenderInfo);
        redirect(step);
      });
      setLoading(false);
    },
    [redirect, step, setSender, updateTracking],
  );

  const stepName = mapStepNumberToStepName(step);

  return (
    <>
      <HideScreenOptions />
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
              <WelcomeWizardPitchMSSStep />
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
