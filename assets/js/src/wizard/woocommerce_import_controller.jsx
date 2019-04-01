import React from 'react';
import MailPoet from 'mailpoet';
import WooCommerceImportListStep from './steps/woo_commerce_import_list_step.jsx';

class WooCommerceImportController extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      loading: false,
    };
    this.updateSettings = this.updateSettings.bind(this);
    this.scheduleImport = this.scheduleImport.bind(this);
    this.finishWizard = this.finishWizard.bind(this);
    this.submit = this.submit.bind(this);
  }

  finishWizard() {
    this.setState({ loading: true });
    window.location = window.finish_wizard_url;
  }

  updateSettings(data) {
    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data,
    }).fail(this.handleApiError);
  }

  scheduleImport() {
    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'importExport',
      action: 'setupWooCommerceInitialImport',
    }).then(() => this.setState({ loading: false })).fail(this.handleApiError);
  }

  handleApiError(response) {
    this.setState({ loading: false });
    let errorMessage = MailPoet.I18n.t('unknownError');
    if (response && response.errors && response.errors.length > 0) {
      errorMessage = response.errors.map(error => error.message);
    }
    MailPoet.Notice.error(errorMessage, { scroll: true });
  }

  submit(importType) {
    this.setState({ loading: true });
    const settings = {
      'woocommerce.import_screen_displayed': 1,
      'mailpoet_subscribe_old_woocommerce_customers.enabled': importType === 'subscribed' ? 1 : 0,
    };
    this.updateSettings(settings).then(this.scheduleImport).then(this.finishWizard);
  }

  render() {
    return (
      <div className="mailpoet_welcome_wizard_steps mailpoet_welcome_wizard_centered_column">
        <div className="mailpoet_welcome_wizard_header">
          <img src={window.mailpoet_logo_url} width="200" height="87" alt="MailPoet logo" />
        </div>
        <WooCommerceImportListStep loading={this.state.loading} submitForm={this.submit} />
      </div>
    );
  }
}

WooCommerceImportController.propTypes = {};

export default WooCommerceImportController;
