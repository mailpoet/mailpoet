const React = require('react');
const ReactDOM = require('react-dom');
const DefaultSender = require('settings/default_sender.jsx').default;

const container = document.getElementById('settings_sender_container');

if (container) {
  ReactDOM.render(
    React.createElement(DefaultSender, {
      senderAddress: window.mailpoet_settings_sender_adddress,
      senderName: window.mailpoet_settings_sender_name,
      replyToAddress: window.mailpoet_settings_reply_to_address,
      replyToName: window.mailpoet_settings_reply_to_name,
    }),
    container
  );
}
