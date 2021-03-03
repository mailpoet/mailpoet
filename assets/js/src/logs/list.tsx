import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { curry } from 'lodash';
import { parseISO } from 'date-fns';

import Datepicker from '../common/datepicker/datepicker';
import { Button } from '../common';
import Input from '../common/form/input/input';
import icon from '../listing/assets/search_icon';

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


export type FilterType = {
  from?: string;
  to?: string;
  search?: string;
}

type ListProps = {
  logs: Logs;
  originalFrom?: string;
  originalTo?: string;
  originalSearch?: string;
  onFilter: (FilterType) => void;
}

export const List: React.FunctionComponent<ListProps> = ({
  logs,
  onFilter,
  originalFrom,
  originalTo,
  originalSearch,
}: ListProps) => {
  const [from, setFrom] = useState(originalFrom);
  const [to, setTo] = useState(originalTo);
  const [search, setSearch] = useState(originalSearch);

  const dateChanged = curry((setter, value): void => {
    // Swap display format to storage format
    const formatting = {
      format: 'Y-m-d',
    };
    setter(MailPoet.Date.format(value, formatting));
  });

  function filterClick(): void {
    const data: FilterType = {};
    if (from) {
      data.from = from;
    }
    if (to) {
      data.to = to;
    }
    if (search && search !== '') {
      data.search = search;
    }
    onFilter(data);
  }

  return (
    <div className="mailpoet-listing mailpoet-logs">

      <div className="mailpoet-listing-header">
        <div className="mailpoet-listing-search">
          <label htmlFor="search_input" className="screen-reader-text">
            {MailPoet.I18n.t('searchLabel')}
          </label>
          <Input
            dimension="small"
            iconStart={icon}
            type="search"
            id="search_input"
            name="s"
            onChange={(event): void => setSearch(event.target.value)}
            value={search}
            placeholder={MailPoet.I18n.t('searchLabel')}
          />
        </div>
        <div className="mailpoet-listing-filters">
          {`${MailPoet.I18n.t('from')}:`}
          <Datepicker
            dateFormat="MMMM d, yyyy"
            onChange={dateChanged(setFrom)}
            maxDate={new Date()}
            selected={from ? parseISO(from) : undefined}
            dimension="small"
          />
          {`${MailPoet.I18n.t('to')}:`}
          <Datepicker
            dateFormat="MMMM d, yyyy"
            onChange={dateChanged(setTo)}
            maxDate={new Date()}
            selected={to ? parseISO(to) : undefined}
            dimension="small"
          />
        </div>
        <Button dimension="small" onClick={filterClick}>
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
};
