import ReactDOM from 'react-dom';
import React from 'react';

import { List, Logs } from './list';

interface LogsWindow extends Window {
  mailpoet_logs: Logs;
}

declare let window: LogsWindow;

const logsContainer = document.getElementById('mailpoet_logs_container');

if (logsContainer) {
  ReactDOM.render(
    <List logs={window.mailpoet_logs} />,
    logsContainer
  );
}
