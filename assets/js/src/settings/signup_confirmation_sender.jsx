import React from 'react';
import PropTypes from 'prop-types';
import { partial } from 'underscore';
import MailPoet from 'mailpoet';
import SenderEmailAddressWarning from 'common/sender_email_address_warning.jsx';

class SignupConfirmationSender extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      senderAddress: props.senderAddress,
      senderName: props.senderName,
    };
    this.onChange = this.onChange.bind(this);
  }

  onChange(field, e) {
    const newState = {};
    newState[field] = e.target.value;
    this.setState(newState);
  }

  render() {
    return (
      <>
        <th scope="row">
          <label htmlFor="settings[signup_confirmation_from_name]">{MailPoet.I18n.t('from')}</label>
        </th>
        <td>
          <p>
            <input
              type="text"
              id="settings[signup_confirmation_from_name]"
              name="signup_confirmation[from][name]"
              data-automation-id="signup_confirmation_email_from_name"
              value={this.state.senderName}
              onChange={partial(this.onChange, 'senderName')}
              placeholder={MailPoet.I18n.t('yourName')}
            />
            <input
              type="email"
              id="settings[signup_confirmation_from_email]"
              name="signup_confirmation[from][address]"
              data-automation-id="signup_confirmation_email_from_email"
              value={this.state.senderAddress}
              onChange={partial(this.onChange, 'senderAddress')}
              placeholder="confirmation@mydomain.com"
              size="28"
            />
          </p>
          <div className="regular-text">
            <SenderEmailAddressWarning
              emailAddress={this.state.senderAddress}
              mssActive={this.props.mssActive}
              pluginInstalledAt={this.props.pluginInstalledAt}
            />
          </div>
        </td>
      </>
    );
  }
}

SignupConfirmationSender.propTypes = {
  senderAddress: PropTypes.string.isRequired,
  senderName: PropTypes.string.isRequired,
  mssActive: PropTypes.bool.isRequired,
  pluginInstalledAt: PropTypes.string.isRequired,
};

export default SignupConfirmationSender;
