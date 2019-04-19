import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import ReactHtmlParser from 'react-html-parser';


class WizardWooCommerceImportListStep extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      importType: null,
    };

    this.handleOptionChange = this.handleOptionChange.bind(this);
    this.submit = this.submit.bind(this);
  }

  handleOptionChange(event) {
    this.setState({
      importType: event.target.value,
    });
  }

  submit(event) {
    event.preventDefault();
    if (!this.state.importType) return false;
    this.props.submitForm(this.state.importType);
    return false;
  }

  render() {
    return (
      <div className="mailpoet_welcome_wizard_step_content mailpoet_welcome_wizard_centered_column">
        <h1>{MailPoet.I18n.t('wooCommerceListImportTitle')}</h1>
        <p>{MailPoet.I18n.t('wooCommerceListImportInfo1')}</p>
        <p>{MailPoet.I18n.t('wooCommerceListImportInfo2')}</p>
        <p><b>{MailPoet.I18n.t('wooCommerceListImportInfo3')}</b></p>
        <form onSubmit={this.submit} className="mailpoet_wizard_woocommerce_list">
          <label htmlFor="import_type_subscribed">
            <input
              id="import_type_subscribed"
              type="radio"
              name="import_type"
              checked={this.state.importType === 'subscribed'}
              onChange={this.handleOptionChange}
              value="subscribed"
            />
            {ReactHtmlParser(MailPoet.I18n.t('wooCommerceListImportCheckboxSubscribed'))}
          </label>
          <label htmlFor="import_type_unsubscribed">
            <input
              id="import_type_unsubscribed"
              type="radio"
              name="import_type"
              checked={this.state.importType === 'unsubscribed'}
              onChange={this.handleOptionChange}
              value="unsubscribed"
            />
            {ReactHtmlParser(MailPoet.I18n.t('wooCommerceListImportCheckboxUnsubscribed'))}
          </label>
          <p>{MailPoet.I18n.t('wooCommerceListImportInfo4')}</p>
          <input
            className="button button-primary"
            type="submit"
            value={MailPoet.I18n.t('wooCommerceListImportSubmit')}
            disabled={!this.state.importType || this.props.loading}
          />
        </form>
      </div>
    );
  }
}

WizardWooCommerceImportListStep.propTypes = {
  submitForm: PropTypes.func.isRequired,
  loading: PropTypes.bool.isRequired,
};

export default WizardWooCommerceImportListStep;
