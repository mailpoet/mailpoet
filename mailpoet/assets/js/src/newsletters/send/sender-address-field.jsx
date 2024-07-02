import { Component } from 'react';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { FormFieldText } from 'form/fields/text.jsx';
import { SenderEmailAddressWarning } from 'common/sender-email-address-warning';
import {
  isFieldValid,
  addOrUpdateError,
  resetFieldError,
  validateField,
} from 'common/functions/parsley-helper-functions';
import { extractEmailDomain } from 'common/functions';

class SenderField extends Component {
  constructor(props) {
    super(props);
    const emailDomain = extractEmailDomain(props.item.sender_address);

    this.state = {
      emailAddress: props.item.sender_address,
      showSenderDomainWarning:
        !window.mailpoet_verified_sender_domains.includes(emailDomain),
      isPartiallyVerifiedDomain:
        window.mailpoet_partially_verified_sender_domains.includes(emailDomain),
      showAuthEmailsError: false,
    };
    this.onChange = this.onChange.bind(this);
    this.onBlur = this.onBlur.bind(this);
    // to allow use the same error message from the last step of sending
    window.mailpoet_sender_address_field_blur = this.onBlur;

    const fieldId = props.field.id || `field_${props.field.name}`;
    this.domElementSelector = `#${fieldId}`;
    this.parsleyFieldName = 'invalidFromAddress';
  }

  componentDidMount() {
    this.validateEmailAddress();
  }

  onChange(event) {
    const { onValueChange = () => {} } = this.props;
    const emailAddress = event.target.value.toLowerCase();
    this.setState({
      emailAddress,
    });
    onValueChange({
      ...event,
      target: {
        ...event.target,
        name: event.target.name,
        value: emailAddress,
      },
    });
    // hide email address and domain warning when user is typing
    this.setState({
      showAuthEmailsError: false,
      showSenderDomainWarning: false,
    });
    resetFieldError(this.domElementSelector, this.parsleyFieldName);
  }

  onBlur() {
    this.validateEmailAddress();
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
      return;
    }
    this.showSenderDomainError(true);
  };

  showSenderDomainError = (status) => {
    if (!status) return;

    this.setState({ showSenderDomainWarning: true });
  };

  validateEmailAddress() {
    if (!window.mailpoet_mss_active) return;

    const emailAddress = this.state.emailAddress;

    const emailDomain = extractEmailDomain(emailAddress);

    if (window.mailpoet_verified_sender_domains.includes(emailDomain)) {
      // allow user send with any email address from verified domains
      return;
    }

    const emailAddressIsAuthorized =
      this.isEmailAddressAuthorized(emailAddress);

    this.showSenderFieldError(emailAddressIsAuthorized, emailAddress);

    this.setState({
      isPartiallyVerifiedDomain:
        window.mailpoet_partially_verified_sender_domains.includes(emailDomain),
    });
  }

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

        <div className="regular-text regular-text-full-width">
          <SenderEmailAddressWarning
            emailAddress={this.state.emailAddress}
            mssActive={window.mailpoet_mss_active}
            isEmailAuthorized={!this.state.showAuthEmailsError}
            showSenderDomainWarning={
              this.state.showSenderDomainWarning &&
              !this.state.showAuthEmailsError
            }
            isPartiallyVerifiedDomain={this.state.isPartiallyVerifiedDomain}
            senderRestrictions={window.mailpoet_sender_restrictions}
            onSuccessfulEmailOrDomainAuthorization={(data) => {
              if (data.type === 'email') {
                this.setState({ showAuthEmailsError: false });

                MailPoet.trackEvent('MSS in plugin authorize email', {
                  'authorized email source': 'newsletter',
                  wasSuccessful: 'yes',
                });
              }
              if (data.type === 'domain') {
                this.setState({ showSenderDomainWarning: false });
                this.setState({ isPartiallyVerifiedDomain: false });

                MailPoet.trackEvent('MSS in plugin verify sender domain', {
                  'verify sender domain source': 'newsletter',
                  wasSuccessful: 'yes',
                });
              }
              resetFieldError(this.domElementSelector, this.parsleyFieldName);
            }}
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

SenderField.displayName = 'SenderField';
export { SenderField };
