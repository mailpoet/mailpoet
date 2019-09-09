import React from 'react';
import ReactDOM from 'react-dom';
import DefaultSender from 'settings/default_sender.jsx';

const settingsSenderContainer = document.getElementById('settings_sender_container');

if (settingsSenderContainer) {
  ReactDOM.render(
    React.createElement(DefaultSender, {
      senderAddress: window.mailpoet_settings_sender_adddress,
      senderName: window.mailpoet_settings_sender_name,
      replyToAddress: window.mailpoet_settings_reply_to_address,
      replyToName: window.mailpoet_settings_reply_to_name,
      mssActive: window.mailpoet_mss_active,
    }),
    settingsSenderContainer
  );
}
