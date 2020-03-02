import React from 'react';
import ReactDOM from 'react-dom';
import DefaultSender from 'old_settings/default_sender.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';

const App = () => (
  <GlobalContext.Provider value={useGlobalContextValue(window)}>
    <Notices />
    <DefaultSender
      senderAddress={window.mailpoet_settings_sender_adddress}
      senderName={window.mailpoet_settings_sender_name}
      replyToAddress={window.mailpoet_settings_reply_to_address}
      replyToName={window.mailpoet_settings_reply_to_name}
      mssActive={window.mailpoet_mss_active}
    />
  </GlobalContext.Provider>
);

const settingsSenderContainer = document.getElementById('settings_sender_container');

if (settingsSenderContainer) {
  ReactDOM.render(<App />, settingsSenderContainer);
}
