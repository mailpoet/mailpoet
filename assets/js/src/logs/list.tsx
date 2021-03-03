import React from 'react';
import MailPoet from 'mailpoet';

type Log = {
  id: number;
  name: string;
  message: string;
  created_at: string;
}

export type Logs = Log[];

type LogProps = {
  log: Log;
}

type ListProps = {
  logs: Logs;
}

const Log: React.FunctionComponent<LogProps> = ({ log }: LogProps) => {
  return (
    <tr key={`log-row-${log.id}`}>
      <td>{log.name}</td>
      <td>
        {log.message.substr(0, 150)}
        …
      </td>
      <td>{MailPoet.Date.full(log.created_at)}</td>
    </tr>
  );
}

export const List: React.FunctionComponent<ListProps> = ({ logs }: ListProps) => (
  <table className="mailpoet-listing-table widefat striped">
    <thead>
      <tr>
        <th>{MailPoet.I18n.t('tableHeaderName')}</th>
        <th>{MailPoet.I18n.t('tableHeaderMessage')}</th>
        <th>{MailPoet.I18n.t('tableHeaderCreatedOn')}</th>
      </tr>
    </thead>
    <tbody>
      {logs.map((log) => <Log log={log} />)}
    </tbody>
  </table>
);
