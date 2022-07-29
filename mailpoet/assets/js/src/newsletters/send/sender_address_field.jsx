import { Component } from 'react';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { FormFieldText } from 'form/fields/text.jsx';
import { SenderEmailAddressWarning } from 'common/sender_email_address_warning.jsx';
import {
  isFieldValid,
  addOrUpdateError,
  resetFieldError,
  validateField,
} from 'common/functions/parsley_helper_functions';
import { extractEmailDomain } from 'common/functions';
import { checkSenderEmailDomainDmarcPolicy } from 'common/check_sender_domain_dmarc_policy';

class SenderField extends Component {
  constructor(props) {
    super(props);
    this.state = {
      emailAddress: props.item.sender_address,
      showSenderDomainWarning: false,
      showAuthEmailsError: false,
    };
    this.onChange = this.onChange.bind(this);
    this.onBlur = this.onBlur.bind(this);

    const fieldId = props.field.id || `field_${props.field.name}`;
    this.domElementSelector = `#${fieldId}`;
    this.parsleyFieldName = 'invalidFromAddress';
  }

  onChange(event) {
    const emailAddress = event.target.value.toLowerCase();
    this.setState({
      emailAddress,
    });
    this.props.onValueChange({
      ...event,
      target: {
        ...event.target,
        name: event.target.name,
        value: emailAddress,
      },
    });
    // hide email address warning when user is typing
    this.setState({ showAuthEmailsError: false });
    resetFieldError(this.domElementSelector, this.parsleyFieldName);
  }

  onBlur() {
    if (!window.mailpoet_mss_active) return;

    const emailAddress = this.state.emailAddress;
    const emailAddressIsAuthorized =
      this.isEmailAddressAuthorized(emailAddress);

    this.showSenderFieldError(emailAddressIsAuthorized, emailAddress);

    // Skip domain DMARC validation if the email is a freemail
    const isFreeDomain =
      MailPoet.freeMailDomains.indexOf(extractEmailDomain(emailAddress)) > -1;
    if (isFreeDomain) return;

    checkSenderEmailDomainDmarcPolicy(emailAddress)
      .then((status) => {
        this.showSenderDomainError(status, emailAddress);
      })
      .catch(() => {
        // do nothing for now when the request fails
      });
  }

  isEmailAddressAuthorized = (email) =>
    (window.mailpoet_authorized_emails || []).includes(email);

  showInvalidFromAddressError = () => {
    // We add an empty error to the parsley validator on the field
    // The error message is too big to fit into space we have for parsley errors. We render it in own component SenderEmailAddressWarning.
    addOrUpdateError(this.domElementSelector, this.parsleyFieldName, ' ');
    this.setState({ showAuthEmailsError: true });
  };

  showSenderFieldError = (emailAddressIsAuthorized, emailAddress) => {
    if (!window.mailpoet_mss_active) return;

    resetFieldError(this.domElementSelector, this.parsleyFieldName);

    if (!isFieldValid(this.domElementSelector)) {
      validateField(this.domElementSelector);
      return;
    }

    if (!emailAddressIsAuthorized) {
      this.showInvalidFromAddressError(emailAddress);
    }
  };

  showSenderDomainError = (status) => {
    if (!status) return;

    this.setState({ showSenderDomainWarning: true });
  };

  render() {
    return (
      <>
        <FormFieldText
          item={{
            ...this.props.item,
            sender_address: this.state.emailAddress,
          }}
          field={this.props.field}
          onValueChange={this.onChange}
          onBlurEvent={this.onBlur}
        />

        <div className="regular-text">
          <SenderEmailAddressWarning
            emailAddress={this.state.emailAddress}
            mssActive={window.mailpoet_mss_active}
            isEmailAuthorized={!this.state.showAuthEmailsError}
            showSenderDomainWarning={this.state.showSenderDomainWarning}
            onSuccessfulEmailOrDomainAuthorization={() =>
              this.setState({ showSenderDomainWarning: false })
            }
          />
        </div>
      </>
    );
  }
}

SenderField.propTypes = {
  field: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  item: PropTypes.shape({
    sender_address: PropTypes.string.isRequired,
  }).isRequired,
  onValueChange: PropTypes.func,
};

SenderField.defaultProps = {
  onValueChange: function onValueChange() {
    // no-op
  },
};

export { SenderField };
