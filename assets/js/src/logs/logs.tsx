import ReactDOM from 'react-dom';
import React from 'react';

import { List, Logs, FilterType } from './list';

interface LogsWindow extends Window {
  mailpoet_logs: Logs;
}

declare let window: LogsWindow;

const logsContainer = document.getElementById('mailpoet_logs_container');

if (logsContainer) {
  const url = new URL(window.location.href);
  ReactDOM.render(
    <List
      logs={window.mailpoet_logs}
      onFilter={(data: FilterType): void => {
        Object.entries(data).forEach(([key, value]) => {
          url.searchParams.append(key, value);
        });
        window.location.href = url.href;
      }}
    />,
    logsContainer
  );
}
