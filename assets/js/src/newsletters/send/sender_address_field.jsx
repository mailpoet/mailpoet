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
    this.setState({ emailAddress: event.target.value });
    this.props.onValueChange(event);
  }

  render() {
    return (
      <React.Fragment>
        <FormFieldText
          {...this.props}
          onValueChange={this.onChange}
        />
        <div className="regular-text">
          <SenderEmailAddressWarning
            emailAddress={this.state.emailAddress}
            mssActive={window.mailpoet_mss_active}
            pluginInstalledAt={window.mailpoet_installed_at}
          />
        </div>
      </React.Fragment>
    );
  }
}

SenderField.propTypes = {
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
