import PropTypes from 'prop-types';
import React, { useState, useEffect } from 'react';
import { partial } from 'underscore';
import MailPoet from 'mailpoet';
import WelcomeWizardSenderStep from './steps/sender_step.jsx';
import WelcomeWizardMigratedUserStep from './steps/migrated_user_step.jsx';
import WelcomeWizardEmailCourseStep from './steps/email_course_step.jsx';
import WelcomeWizardUsageTrackingStep from './steps/usage_tracking_step.jsx';
import WelcomeWizardWooCommerceStep from './steps/woo_commerce_step.jsx';
import WelcomeWizardPitchMSSStep from './steps/pitch_mss/step.jsx';
import WelcomeWizardStepLayout from './steps/step_layout.jsx';

import CreateSenderSettings from './create_sender_settings.jsx';
import { getStepsCount, redirectToNextStep } from './steps_numbers.jsx';

const WelcomeWizardStepsController = (props) => {
  const stepsCount = getStepsCount();
  const shouldSetSender = !window.is_mp2_migration_complete;
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

  function activateTracking() {
    updateSettings({ analytics: { enabled: true } }).then(() => (
      redirect(step)));
  }

  function updateSender(data) {
    setSender({ ...sender, ...data });
  }

  function submitSender() {
    updateSettings(CreateSenderSettings(sender))
      .then(() => (redirect(step)));
  }

  function skipSenderStep() {
    setLoading(true);
    updateSettings(CreateSenderSettings({ address: window.admin_email, name: '' }))
      .then(finishWizard);
  }

  return (
    <div className="mailpoet_welcome_wizard_steps">
      {step === 1 && shouldSetSender
        ? (
          <WelcomeWizardStepLayout
            step={step}
            stepsCount={stepsCount}
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
        ) : null
      }

      { step === 1 && !shouldSetSender
        ? (
          <WelcomeWizardStepLayout
            step={step}
            stepsCount={stepsCount}
            illustrationUrl={window.wizard_sender_illustration_url}
          >
            <WelcomeWizardMigratedUserStep
              next={() => redirect(step)}
            />
          </WelcomeWizardStepLayout>
        ) : null
      }

      { step === 2
        ? (
          <WelcomeWizardStepLayout
            step={step}
            stepsCount={stepsCount}
            illustrationUrl={window.wizard_email_course_illustration_url}
          >
            <WelcomeWizardEmailCourseStep
              next={() => redirect(step)}
            />
          </WelcomeWizardStepLayout>
        ) : null
      }

      { step === 3
        ? (
          <WelcomeWizardStepLayout
            step={step}
            stepsCount={stepsCount}
            illustrationUrl={window.wizard_tracking_illustration_url}
          >
            <WelcomeWizardUsageTrackingStep
              skip_action={() => redirect(step)}
              allow_action={activateTracking}
              allow_text={stepsCount > 3
                ? MailPoet.I18n.t('allowAndContinue') : MailPoet.I18n.t('allowAndFinish')}
              loading={loading}
            />
          </WelcomeWizardStepLayout>
        ) : null
      }

      { step === 4
        ? (
          <WelcomeWizardStepLayout
            step={step}
            stepsCount={stepsCount}
            illustrationUrl={window.wizard_woocommerce_illustration_url}
          >
            <WelcomeWizardWooCommerceStep
              next={() => redirect(step)}
              screenshot_src={window.wizard_woocommerce_box_url}
              loading={loading}
            />
          </WelcomeWizardStepLayout>
        ) : null
      }

      { step === 5
        ? (
          <WelcomeWizardStepLayout
            step={step}
            stepsCount={stepsCount}
            illustrationUrl={window.wizard_tracking_illustration_url}
          >
            <WelcomeWizardPitchMSSStep
              next={() => redirect(step)}
            />
          </WelcomeWizardStepLayout>
        ) : null
      }
    </div>
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
