import React from 'react';
import PropTypes from 'prop-types';
import { partial } from 'underscore';
import MailPoet from 'mailpoet';
import SenderEmailAddressWarning from 'common/sender_email_address_warning.jsx';

class DefaultSender extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      senderAddress: props.senderAddress,
      senderName: props.senderName,
      replyToName: props.replyToName,
      replyToAddress: props.replyToAddress,
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
        <p>
          <label htmlFor="settings[from_name]">{MailPoet.I18n.t('from')}</label>
          <input
            type="text"
            id="settings[from_name]"
            data-automation-id="settings-page-from-name-field"
            name="sender[name]"
            value={this.state.senderName}
            onChange={partial(this.onChange, 'senderName')}
            placeholder={MailPoet.I18n.t('yourName')}
          />
          <input
            type="email"
            id="settings[from_email]"
            name="sender[address]"
            data-automation-id="settings-page-from-email-field"
            value={this.state.senderAddress}
            onChange={partial(this.onChange, 'senderAddress')}
            placeholder="from@mydomain.com"
          />
        </p>
        <div className="regular-text">
          <SenderEmailAddressWarning
            emailAddress={this.state.senderAddress}
            mssActive={this.props.mssActive}
          />
        </div>
        <p>
          <label htmlFor="settings[reply_name]">{MailPoet.I18n.t('replyTo')}</label>
          <input
            type="text"
            id="settings[reply_name]"
            name="reply_to[name]"
            value={this.state.replyToName}
            onChange={partial(this.onChange, 'replyToName')}
            placeholder={MailPoet.I18n.t('yourName')}
          />
          <input
            type="email"
            id="settings[reply_email]"
            name="reply_to[address]"
            value={this.state.replyToAddress}
            onChange={partial(this.onChange, 'replyToAddress')}
            placeholder="reply_to@mydomain.com"
          />
        </p>
      </>
    );
  }
}

DefaultSender.propTypes = {
  senderAddress: PropTypes.string.isRequired,
  senderName: PropTypes.string.isRequired,
  replyToAddress: PropTypes.string.isRequired,
  replyToName: PropTypes.string.isRequired,
  mssActive: PropTypes.bool.isRequired,
};

export default DefaultSender;
