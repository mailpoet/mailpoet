import React from 'react';
import ReactDOM from 'react-dom';
import DefaultSender from 'settings/default_sender.jsx';
import SignupConfirmationSender from 'settings/signup_confirmation_sender.jsx';

const settingsSenderContainer = document.getElementById('settings_sender_container');

if (settingsSenderContainer) {
  ReactDOM.render(
    React.createElement(DefaultSender, {
      senderAddress: window.mailpoet_settings_sender_adddress,
      senderName: window.mailpoet_settings_sender_name,
      replyToAddress: window.mailpoet_settings_reply_to_address,
      replyToName: window.mailpoet_settings_reply_to_name,
      pluginInstalledAt: window.mailpoet_installed_at,
      mssActive: window.mailpoet_mss_active,
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
      pluginInstalledAt: window.mailpoet_installed_at,
      mssActive: window.mailpoet_mss_active,
    }),
    settingsSignupConfirmationSenderContainer
  );
}
