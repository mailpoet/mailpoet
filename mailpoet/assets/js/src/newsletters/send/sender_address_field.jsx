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
  }

  onChange(event) {
    const emailAddressIsAuthorized =
      event.target.value.length >= 3
        ? this.isEmailAddressAuthorized(event.target.value.toLowerCase())
        : true;

    this.setState({
      emailAddress: event.target.value.toLowerCase(),
      isEmailAuthorized: emailAddressIsAuthorized,
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
        />
        <div className="regular-text">
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
