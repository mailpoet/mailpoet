const React = require('react');
const ReactDOM = require('react-dom');
const DefaultSender = require('settings/default_sender.jsx').default;
const SignupConfirmationSender = require('settings/signup_confirmation_sender.jsx').default;

const settingsSenderContainer = document.getElementById('settings_sender_container');

if (settingsSenderContainer) {
  ReactDOM.render(
    React.createElement(DefaultSender, {
      senderAddress: window.mailpoet_settings_sender_adddress,
      senderName: window.mailpoet_settings_sender_name,
      replyToAddress: window.mailpoet_settings_reply_to_address,
      replyToName: window.mailpoet_settings_reply_to_name,
    }),
    settingsSenderContainer
  );
}

const settingsSignupConfirmationSenderContainer = document.getElementById('settings_signup_confirmation_sender_container');

if (settingsSignupConfirmationSenderContainer) {
  ReactDOM.render(
    React.createElement(SignupConfirmationSender, {
      senderAddress: window.mailpoet_settings_sender_adddress,
      senderName: window.mailpoet_settings_sender_name,
    }),
    settingsSignupConfirmationSenderContainer
  );
}
