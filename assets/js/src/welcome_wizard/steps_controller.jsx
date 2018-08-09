import React from 'react';
import MailPoet from 'mailpoet';
import WelcomeWizardHeader from './header.jsx';
import WelcomeWizardSenderStep from './steps/sender_step.jsx';
import WelcomeWizardMigratedUserStep from './steps/migrated_user_step.jsx';
import WelcomeWizardHelpInfoStep from './steps/help_info_step.jsx';
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
    };

    this.finishWizard = this.finishWizard.bind(this);
    this.updateSettings = this.updateSettings.bind(this);
    this.activateTracking = this.activateTracking.bind(this);
    this.updateSender = this.updateSender.bind(this);
    this.submitSender = this.submitSender.bind(this);
    this.showWooCommerceStepOrFinish = this.showWooCommerceStepOrFinish.bind(this);
    this.componentDidUpdate();
  }

  componentDidUpdate() {
    const step = parseInt(this.props.params.step, 10);
    if (step > this.state.stepsCount || step < 1) {
      this.props.router.push('steps/1');
    }
  }

  finishWizard() {
    this.setState({ loading: true });
    window.location = window.finish_wizard_url;
  }

  showWooCommerceStepOrFinish() {
    if (this.state.stepsCount === 4) {
      this.props.router.push('steps/4');
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
    }).then(() => this.setState({ loading: false })
    ).fail((response) => {
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
      this.showWooCommerceStepOrFinish())
    );
  }

  updateSender(data) {
    this.setState({
      sender: Object.assign({}, this.state.sender, data),
    });
  }

  submitSender() {
    this.updateSettings({ sender: this.state.sender }).then(() => (this.props.router.push('steps/2')));
  }

  render() {
    const step = parseInt(this.props.params.step, 10);
    return (
      <div className="mailpoet_welcome_wizard_steps mailpoet_welcome_wizard_centered_column">
        <WelcomeWizardHeader
          current_step={step}
          steps_count={this.state.stepsCount}
          logo_src={window.mailpoet_logo_url}
        />
        { step === 1 && this.state.shouldSetSender ?
          <WelcomeWizardSenderStep
            update_sender={this.updateSender}
            submit_sender={this.submitSender}
            finish={this.finishWizard}
            loading={this.state.loading}
            sender={this.state.sender}
          /> : null
        }

        { step === 1 && !this.state.shouldSetSender ?
          <WelcomeWizardMigratedUserStep
            next={() => this.props.router.push('steps/2')}
          /> : null
        }

        { step === 2 ?
          <WelcomeWizardHelpInfoStep
            next={() => this.props.router.push('steps/3')}
          /> : null
        }

        { step === 3 ?
          <WelcomeWizardUsageTrackingStep
            skip_action={this.showWooCommerceStepOrFinish}
            allow_action={this.activateTracking}
            allow_text={this.state.stepsCount === 4 ?
              MailPoet.I18n.t('allowAndContinue') : MailPoet.I18n.t('allowAndFinish')}
            loading={this.state.loading}
          /> : null
        }

        { step === 4 ?
          <WelcomeWizardWooCommerceStep
            next={this.finishWizard}
            screenshot_src={window.woocommerce_screenshot_url}
            loading={this.state.loading}
          /> : null
        }
      </div>
    );
  }
}

WelcomeWizardStepsController.propTypes = {
  params: React.PropTypes.shape({
    step: React.PropTypes.string.isRequired,
  }).isRequired,
  router: React.PropTypes.shape({
    push: React.PropTypes.func.isRequired,
  }).isRequired,
};

module.exports = WelcomeWizardStepsController;
