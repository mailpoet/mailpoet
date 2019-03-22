import PropTypes from 'prop-types';
import React, { useState, useEffect } from 'react';
import MailPoet from 'mailpoet';
import WelcomeWizardHeader from './header.jsx';
import WelcomeWizardSenderStep from './steps/sender_step.jsx';
import WelcomeWizardMigratedUserStep from './steps/migrated_user_step.jsx';
import WelcomeWizardEmailCourseStep from './steps/email_course_step.jsx';
import WelcomeWizardUsageTrackingStep from './steps/usage_tracking_step.jsx';
import WelcomeWizardWooCommerceStep from './steps/woo_commerce_step.jsx';

const WelcomeWizardStepsController = (props) => {
  const stepsCount = window.is_woocommerce_active ? 4 : 3;
  const shouldSetSender = !window.is_mp2_migration_complete;
  const step = parseInt(props.match.params.step, 10);

  const [loading, setLoading] = useState(false);
  const [sender, setSender] = useState(window.sender_data);
  const [replyTo, setReplyTo] = useState(window.reply_to_data);

  useEffect(() => {
    if (step > stepsCount || step < 1) {
      props.history.push('/steps/1');
    }
  }, [step, stepsCount, props.history]);

  function finishWizard() {
    setLoading(true);
    window.location = window.finish_wizard_url;
  }

  function showWooCommerceStepOrFinish() {
    if (stepsCount === 4) {
      props.history.push('/steps/4');
    } else {
      finishWizard();
    }
  }

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
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    });
  }

  function activateTracking() {
    updateSettings({ analytics: { enabled: true } }).then(() => (
      showWooCommerceStepOrFinish()));
  }

  function updateSender(data) {
    setSender({ ...sender, ...data });
  }

  function updateReplyTo(data) {
    setReplyTo({ ...replyTo, ...data });
  }

  function submitSender() {
    updateSettings({
      sender,
      reply_to: replyTo,
    }).then(() => (props.history.push('/steps/2')));
  }

  return (
    <div className="mailpoet_welcome_wizard_steps mailpoet_welcome_wizard_centered_column">
      <WelcomeWizardHeader
        current_step={step}
        steps_count={stepsCount}
        logo_src={window.mailpoet_logo_url}
      />
      { step === 1 && shouldSetSender
        ? (
          <WelcomeWizardSenderStep
            update_sender={updateSender}
            submit_sender={submitSender}
            update_reply_to={updateReplyTo}
            finish={finishWizard}
            loading={loading}
            sender={sender}
            reply_to={replyTo}
          />
        ) : null
      }

      { step === 1 && !shouldSetSender
        ? (
          <WelcomeWizardMigratedUserStep
            next={() => props.history.push('/steps/2')}
          />
        ) : null
      }

      { step === 2
        ? (
          <WelcomeWizardEmailCourseStep
            next={() => props.history.push('/steps/3')}
            illustration_url={window.email_course_illustration}
          />
        ) : null
      }

      { step === 3
        ? (
          <WelcomeWizardUsageTrackingStep
            skip_action={showWooCommerceStepOrFinish}
            allow_action={activateTracking}
            allow_text={stepsCount === 4
              ? MailPoet.I18n.t('allowAndContinue') : MailPoet.I18n.t('allowAndFinish')}
            loading={loading}
          />
        ) : null
      }

      { step === 4
        ? (
          <WelcomeWizardWooCommerceStep
            next={finishWizard}
            screenshot_src={window.woocommerce_screenshot_url}
            loading={loading}
          />
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
