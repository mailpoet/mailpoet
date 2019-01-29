import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import WelcomeWizardHeader from './header.jsx';
import WelcomeWizardSenderStep from './steps/sender_step.jsx';
import WelcomeWizardMigratedUserStep from './steps/migrated_user_step.jsx';
import WelcomeWizardEmailCourseStep from './steps/email_course_step.jsx';
import WelcomeWizardUsageTrackingStep from './steps/usage_tracking_step.jsx';
import WelcomeWizardWooCommerceStep from './steps/woo_commerce_step.jsx';

class WelcomeWizardStepsController extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      stepsCount: window.is_woocommerce_active ? 4 : 3,
      shouldSetSender: !window.is_mp2_migration_complete,
      loading: false,
      sender: window.sender_data,
      replyTo: window.reply_to_data,
    };

    this.finishWizard = this.finishWizard.bind(this);
    this.updateSettings = this.updateSettings.bind(this);
    this.activateTracking = this.activateTracking.bind(this);
    this.updateSender = this.updateSender.bind(this);
    this.updateReplyTo = this.updateReplyTo.bind(this);
    this.submitSender = this.submitSender.bind(this);
    this.showWooCommerceStepOrFinish = this.showWooCommerceStepOrFinish.bind(this);
    this.componentDidUpdate();
  }

  componentDidUpdate() {
    const step = parseInt(this.props.match.params.step, 10);
    if (step > this.state.stepsCount || step < 1) {
      this.props.history.push('/steps/1');
    }
  }

  finishWizard() {
    this.setState({ loading: true });
    window.location = window.finish_wizard_url;
  }

  showWooCommerceStepOrFinish() {
    if (this.state.stepsCount === 4) {
      this.props.history.push('/steps/4');
    } else {
      this.finishWizard();
    }
  }

  updateSettings(data) {
    this.setState({ loading: true });
    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data,
    }).then(() => this.setState({ loading: false })).fail((response) => {
      this.setState({ loading: false });
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(error => error.message),
          { scroll: true }
        );
      }
    });
  }

  activateTracking() {
    this.updateSettings({ analytics: { enabled: true } }).then(() => (
      this.showWooCommerceStepOrFinish()));
  }

  updateSender(data) {
    this.setState(prevState => ({
      sender: { ...prevState.sender, ...data },
    }));
  }

  updateReplyTo(data) {
    this.setState(prevState => ({
      replyTo: { ...prevState.replyTo, ...data },
    }));
  }

  submitSender() {
    this.updateSettings({
      sender: this.state.sender,
      reply_to: this.state.replyTo,
    }).then(() => (this.props.history.push('/steps/2')));
  }

  render() {
    const step = parseInt(this.props.match.params.step, 10);
    return (
      <div className="mailpoet_welcome_wizard_steps mailpoet_welcome_wizard_centered_column">
        <WelcomeWizardHeader
          current_step={step}
          steps_count={this.state.stepsCount}
          logo_src={window.mailpoet_logo_url}
        />
        { step === 1 && this.state.shouldSetSender
          ? (
            <WelcomeWizardSenderStep
              update_sender={this.updateSender}
              submit_sender={this.submitSender}
              update_reply_to={this.updateReplyTo}
              finish={this.finishWizard}
              loading={this.state.loading}
              sender={this.state.sender}
              reply_to={this.state.replyTo}
            />
          ) : null
        }

        { step === 1 && !this.state.shouldSetSender
          ? (
            <WelcomeWizardMigratedUserStep
              next={() => this.props.history.push('/steps/2')}
            />
          ) : null
        }

        { step === 2
          ? (
            <WelcomeWizardEmailCourseStep
              next={() => this.props.history.push('/steps/3')}
              illustration_url={window.email_course_illustration}
            />
          ) : null
        }

        { step === 3
          ? (
            <WelcomeWizardUsageTrackingStep
              skip_action={this.showWooCommerceStepOrFinish}
              allow_action={this.activateTracking}
              allow_text={this.state.stepsCount === 4
                ? MailPoet.I18n.t('allowAndContinue') : MailPoet.I18n.t('allowAndFinish')}
              loading={this.state.loading}
            />
          ) : null
        }

        { step === 4
          ? (
            <WelcomeWizardWooCommerceStep
              next={this.finishWizard}
              screenshot_src={window.woocommerce_screenshot_url}
              loading={this.state.loading}
            />
          ) : null
        }
      </div>
    );
  }
}

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
