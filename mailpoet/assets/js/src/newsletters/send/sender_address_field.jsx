import { Component } from 'react';
import PropTypes from 'prop-types';
import { FormFieldText } from 'form/fields/text.jsx';
import { SenderEmailAddressWarning } from 'common/sender_email_address_warning.jsx';

class SenderField extends Component {
  constructor(props) {
    super(props);
    this.state = {
      emailAddress: props.item.sender_address,
      isEmailAuthorized: this.isEmailAddressAuthorized(
        props.item.sender_address,
      ),
    };
    this.onChange = this.onChange.bind(this);
    this.onBlur = this.onBlur.bind(this);
  }

  onChange(event) {
    this.setState({
      emailAddress: event.target.value.toLowerCase(),
      isEmailAuthorized: true, // hide email address warning when user is typing
    });
    this.props.onValueChange({
      ...event,
      target: {
        ...event.target,
        name: event.target.name,
        value: event.target.value.toLowerCase(),
      },
    });
  }

  onBlur(event) {
    const emailAddress = event.target.value.toLowerCase();
    const emailAddressIsAuthorized =
      event.target.value.length >= 3
        ? this.isEmailAddressAuthorized(emailAddress)
        : true;

    this.setState({
      isEmailAuthorized: emailAddressIsAuthorized,
      emailAddress,
    });
  }

  isEmailAddressAuthorized = (email) =>
    window.mailpoet_authorized_emails.includes(email);

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
        <div className="regular-text" style={{ marginTop: '2.5rem' }}>
          <SenderEmailAddressWarning
            emailAddress={this.state.emailAddress}
            mssActive={window.mailpoet_mss_active}
            isEmailAuthorized={this.state.isEmailAuthorized}
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
