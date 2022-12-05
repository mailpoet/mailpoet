import { useCallback, useState } from 'react';
import { MailPoet } from 'mailpoet';
import { curry } from 'lodash';
import { parseISO } from 'date-fns';

import { Datepicker } from '../common/datepicker/datepicker';
import { Button, ErrorBoundary, Input } from '../common';
import { Icon } from '../listing/assets/search_icon';

type LogData = {
  id: number;
  name: string;
  message: string;
  created_at: string;
};

export type Logs = LogData[];

function isCtrl(event: {
  ctrlKey?: boolean;
  metaKey?: boolean;
  altKey?: boolean;
}): boolean {
  return (event.ctrlKey || event.metaKey) && !event.altKey; // shiftKey allowed
}

type MessageProps = {
  message: string;
  editing: boolean;
};

function Message({ message, editing }: MessageProps): JSX.Element {
  if (!editing) {
    return <>{`${message.substr(0, 150)}…`}</>;
  }
  return (
    <textarea value={message} className="mailpoet-logs-full-message" readOnly />
  );
}

type LogProps = {
  log: LogData;
};

function Log({ log }: LogProps): JSX.Element {
  const [editing, setEditing] = useState(false);

  function messageClick(event: {
    ctrlKey?: boolean;
    metaKey?: boolean;
    altKey?: boolean;
  }): void {
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
}

Log.displayName = 'Log';
export type FilterType = {
  from?: string;
  to?: string;
  search?: string;
  offset?: string;
  limit?: string;
};

type ListProps = {
  logs: Logs;
  originalFrom?: string;
  originalTo?: string;
  originalSearch?: string;
  originalOffset?: string;
  originalLimit?: string;
  onFilter: (FilterType) => void;
};

function List({
  logs,
  onFilter,
  originalFrom,
  originalTo,
  originalSearch,
  originalOffset,
  originalLimit,
}: ListProps): JSX.Element {
  const [from, setFrom] = useState(originalFrom ?? undefined);
  const [to, setTo] = useState(originalTo ?? undefined);
  const [offset, setOffset] = useState(originalOffset ?? '');
  const [limit, setLimit] = useState(originalLimit ?? '');
  const [search, setSearch] = useState(originalSearch || '');

  const dateChanged = curry(
    (setter: (value: string) => void, value: string): void => {
      if (value === null) {
        setter(undefined);
        return;
      }
      // Swap display format to storage format
      const formatting = {
        format: 'Y-m-d',
      };
      setter(MailPoet.Date.format(value, formatting));
    },
  );

  const filterClick = useCallback((): void => {
    const data: FilterType = {};
    if (from) {
      data.from = from;
    }
    if (to) {
      data.to = to;
    }
    if (offset && offset.trim() !== '') {
      data.offset = offset;
    }
    if (limit && limit.trim() !== '') {
      data.limit = limit;
    }
    if (search && search.trim() !== '') {
      data.search = search.trim();
    }
    onFilter(data);
  }, [from, limit, offset, search, to, onFilter]);

  return (
    <div className="mailpoet-listing mailpoet-logs">
      <div className="mailpoet-listing-header">
        <div className="mailpoet-listing-search">
          <label htmlFor="search_input" className="screen-reader-text">
            {MailPoet.I18n.t('searchLabel')}
          </label>
          <Input
            dimension="small"
            iconStart={Icon}
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
          <ErrorBoundary>
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
          </ErrorBoundary>
        </div>
        <div className="mailpoet-logs-limit">
          <label htmlFor="offset_input" className="screen-reader-text">
            {MailPoet.I18n.t('offsetLabel')}
          </label>
          <Input
            dimension="small"
            id="offset_input"
            name="o"
            type="number"
            onChange={(event): void => setOffset(event.target.value)}
            value={offset}
            placeholder={MailPoet.I18n.t('offsetLabel')}
          />
        </div>
        <div className="mailpoet-logs-limit">
          <label htmlFor="limit_input" className="screen-reader-text">
            {MailPoet.I18n.t('limitLabel')}
          </label>
          <Input
            dimension="small"
            id="limit_input"
            name="l"
            type="number"
            onChange={(event): void => setLimit(event.target.value)}
            value={limit}
            placeholder={MailPoet.I18n.t('limitLabel')}
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
          <ErrorBoundary>
            {logs.map((log) => (
              <Log log={log} key={`log-${log.id}`} />
            ))}
          </ErrorBoundary>
        </tbody>
      </table>
    </div>
  );
}

List.displayName = 'LogsList';

export { List };
