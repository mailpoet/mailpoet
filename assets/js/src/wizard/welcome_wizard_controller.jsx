import PropTypes from 'prop-types';
import React, { useState, useEffect } from 'react';
import { partial } from 'underscore';
import MailPoet from 'mailpoet';
import WelcomeWizardSenderStep from './steps/sender_step.jsx';
import WelcomeWizardMigratedUserStep from './steps/migrated_user_step.jsx';
import WelcomeWizardEmailCourseStep from './steps/email_course_step.jsx';
import WelcomeWizardUsageTrackingStep from './steps/usage_tracking_step.jsx';
import WelcomeWizardPitchMSSStep from './steps/pitch_mss_step.jsx';
import WooCommerceController from './woocommerce_controller.jsx';
import WelcomeWizardStepLayout from './layout/step_layout.jsx';

import CreateSenderSettings from './create_sender_settings.jsx';
import { getStepsCount, redirectToNextStep, mapStepNumberToStepName } from './steps_numbers.jsx';
import Steps from '../common/steps/steps';
import StepsContent from '../common/steps/steps_content';

const WelcomeWizardStepsController = (props) => {
  const stepsCount = getStepsCount();
  const step = parseInt(props.match.params.step, 10);

  const [loading, setLoading] = useState(false);
  const [sender, setSender] = useState(window.sender_data);

  useEffect(() => {
    if (step > stepsCount || step < 1) {
      props.history.push('/steps/1');
    }
  }, [step, stepsCount, props.history]);

  function finishWizard() {
    setLoading(true);
    window.location = window.finish_wizard_url;
  }

  const redirect = partial(redirectToNextStep, props.history, finishWizard);

  function updateSettings(data) {
    setLoading(true);
    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data,
    }).then(() => setLoading(false)).fail((response) => {
      setLoading(false);
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true }
        );
      }
    });
  }

  function submitTracking(tracking, libs3rdParty) {
    setLoading(true);
    updateSettings({
      analytics: { enabled: tracking ? '1' : '' },
      '3rd_party_libs': { enabled: libs3rdParty ? '1' : '' },
    }).then(() => (redirect(step)));
  }

  function updateSender(data) {
    setSender({ ...sender, ...data });
  }

  function submitSender() {
    updateSettings(CreateSenderSettings(sender))
      .then(() => (redirect(step)));
  }

  function skipSenderStep(e) {
    e.preventDefault();
    setLoading(true);
    updateSettings(CreateSenderSettings({ address: window.admin_email, name: '' }))
      .then(() => {
        if (window.is_woocommerce_active) {
          redirect(stepsCount - 1);
        } else {
          finishWizard();
        }
      });
  }

  const stepName = mapStepNumberToStepName(step);

  return (
    <>
      <Steps count={stepsCount} current={step} />
      <StepsContent>
        { stepName === 'WelcomeWizardSenderStep'
          ? (
            <WelcomeWizardStepLayout
              illustrationUrl={window.wizard_sender_illustration_url}
            >
              <WelcomeWizardSenderStep
                update_sender={updateSender}
                submit_sender={submitSender}
                finish={skipSenderStep}
                loading={loading}
                sender={sender}
              />
            </WelcomeWizardStepLayout>
          ) : null}

        { stepName === 'WelcomeWizardMigratedUserStep'
          ? (
            <WelcomeWizardStepLayout
              illustrationUrl={window.wizard_sender_illustration_url}
            >
              <WelcomeWizardMigratedUserStep
                next={() => redirect(step)}
              />
            </WelcomeWizardStepLayout>
          ) : null}

        { stepName === 'WelcomeWizardEmailCourseStep'
          ? (
            <WelcomeWizardStepLayout
              illustrationUrl={window.wizard_email_course_illustration_url}
            >
              <WelcomeWizardEmailCourseStep
                next={() => redirect(step)}
              />
            </WelcomeWizardStepLayout>
          ) : null}

        { stepName === 'WelcomeWizardUsageTrackingStep'
          ? (
            <WelcomeWizardStepLayout
              illustrationUrl={window.wizard_tracking_illustration_url}
            >
              <WelcomeWizardUsageTrackingStep
                loading={loading}
                submitForm={submitTracking}
              />
            </WelcomeWizardStepLayout>
          ) : null}

        { stepName === 'WelcomeWizardPitchMSSStep'
          ? (
            <WelcomeWizardStepLayout
              illustrationUrl={window.wizard_MSS_pitch_illustration_url}
            >
              <WelcomeWizardPitchMSSStep
                next={() => redirect(step)}
                subscribersCount={window.subscribers_count}
                mailpoetAccountUrl={window.mailpoet_account_url}
              />
            </WelcomeWizardStepLayout>
          ) : null}

        { stepName === 'WizardWooCommerceStep'
          ? (
            <WooCommerceController isWizardStep />
          ) : null}
      </StepsContent>
    </>
  );
};

WelcomeWizardStepsController.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      step: PropTypes.string,
    }).isRequired,
  }).isRequired,
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default WelcomeWizardStepsController;
