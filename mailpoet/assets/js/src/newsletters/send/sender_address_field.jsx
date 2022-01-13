import React from 'react';
import FormFieldText from 'form/fields/text.jsx';
import PropTypes from 'prop-types';
import SenderEmailAddressWarning from 'common/sender_email_address_warning.jsx';

class SenderField extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      emailAddress: props.item.sender_address,
    };
    this.onChange = this.onChange.bind(this);
  }

  onChange(event) {
    this.setState({ emailAddress: event.target.value.toLowerCase() });
    this.props.onValueChange({
      ...event,
      target: {
        ...event.target,
        name: event.target.name,
        value: event.target.value.toLowerCase(),
      },
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
        />
        <div className="regular-text">
          <SenderEmailAddressWarning
            emailAddress={this.state.emailAddress}
            mssActive={window.mailpoet_mss_active}
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

export default SenderField;
