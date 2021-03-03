import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { identity } from 'lodash';

import Datepicker from '../common/datepicker/datepicker';
import ListingSearch from '../listing/search';
import { Button } from '../common';

type Log = {
  id: number;
  name: string;
  message: string;
  created_at: string;
}

export type Logs = Log[];

function isCtrl(event: MouseEvent): boolean {
  return (event.ctrlKey || event.metaKey) && !event.altKey; // shiftKey allowed
}

type MessageProps = {
  message: string;
  editing: boolean;
};

const Message: React.FunctionComponent<MessageProps> = ({ message, editing }: MessageProps) => {
  if (!editing) {
    return (<>{`${message.substr(0, 150)}…`}</>);
  }
  return (
    <textarea value={message} className="mailpoet-logs-full-message" readOnly />
  );
};

type LogProps = {
  log: Log;
}

const Log: React.FunctionComponent<LogProps> = ({ log }: LogProps) => {
  const [editing, setEditing] = useState(false);

  function messageClick(event): void {
    if (!isCtrl(event)) return;
    if (editing) return;
    setEditing(true);
  }

  return (
    <tr key={`log-row-${log.id}`}>
      <td role="gridcell">{log.name}</td>
      <td onClick={messageClick} role="gridcell">
        <Message message={log.message} editing={editing} />
      </td>
      <td role="gridcell">{MailPoet.Date.full(log.created_at)}</td>
    </tr>
  );
};

type ListProps = {
  logs: Logs;
}

export const List: React.FunctionComponent<ListProps> = ({ logs }: ListProps) => (
  <div className="mailpoet-listing mailpoet-logs">
    <div className="mailpoet-listing-header">
      <ListingSearch search="" onSearch={identity} />
      <div className="mailpoet-listing-filters">
        {`${MailPoet.I18n.t('from')}:`}
        <Datepicker
          onChange={identity}
          dimension="small"
        />
        {`${MailPoet.I18n.t('to')}:`}
        <Datepicker
          onChange={identity}
          dimension="small"
        />
      </div>
      <Button dimension="small">
        {MailPoet.I18n.t('filter')}
      </Button>
    </div>
    <table className="mailpoet-listing-table widefat striped" role="grid">
      <thead>
        <tr>
          <th>{MailPoet.I18n.t('tableHeaderName')}</th>
          <th>{MailPoet.I18n.t('tableHeaderMessage')}</th>
          <th>{MailPoet.I18n.t('tableHeaderCreatedOn')}</th>
        </tr>
      </thead>
      <tbody>
        {logs.map((log) => <Log log={log} key={`log-${log.id}`} />)}
      </tbody>
    </table>
  </div>
);
