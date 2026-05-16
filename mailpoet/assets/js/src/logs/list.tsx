import { useCallback, useState } from 'react';
import { MailPoet } from 'mailpoet';
import { curry } from 'lodash';
import { parseISO } from 'date-fns';
import { __, _x } from '@wordpress/i18n';

import { Datepicker } from '../common/datepicker/datepicker';
import { Button, ErrorBoundary, Input } from '../common';
import { Icon } from '../listing/assets/search-icon';

type LogData = {
  id: number;
  name: string;
  message: string;
  created_at: string;
};

export type Logs = LogData[];

type LogProps = {
  log: LogData;
};

function Log({ log }: LogProps): JSX.Element {
  const [showFullMessage, setShowFullMessage] = useState(false);

  const toggleFullMessage = () => {
    setShowFullMessage(!showFullMessage);
  };

  return (
    <tr key={`log-row-${log.id}`}>
      <td role="gridcell" className="mailpoet-logs-min-width">
        {log.name}
      </td>
      <td role="gridcell">
        <div
          className={`mailpoet-logs-message ${
            showFullMessage ? 'mailpoet-logs-message-full' : ''
          }`}
        >
          {log.message}
        </div>
      </td>
      <td role="gridcell" className="mailpoet-logs-min-width">
        <Button
          dimension="small"
          variant="secondary"
          onClick={toggleFullMessage}
        >
          {showFullMessage
            ? __('Show less', 'mailpoet')
            : __('Show more', 'mailpoet')}
        </Button>
      </td>
      <td className="mailpoet-logs-min-width" role="gridcell">
        {MailPoet.Date.full(log.created_at)}
      </td>
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
            {__('Search', 'mailpoet')}
          </label>
          <Input
            dimension="small"
            iconStart={Icon}
            type="search"
            id="search_input"
            name="s"
            onChange={(event): void => setSearch(event.target.value)}
            value={search}
            placeholder={__('Search', 'mailpoet')}
          />
        </div>
        <div className="mailpoet-listing-filters">
          {
            // translators: Date from when filtering
            `${__('From', 'mailpoet')}:`
          }
          <ErrorBoundary>
            <Datepicker
              dateFormat="MMMM d, yyyy"
              onChange={dateChanged(setFrom)}
              maxDate={new Date()}
              selected={from ? parseISO(from) : undefined}
              dimension="small"
            />
            {
              // translators: Date to when filtering
              `${__('To', 'mailpoet')}:`
            }
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
            {
              // translators: Offset of search results when filtering
              __('Offset', 'mailpoet')
            }
          </label>
          <Input
            dimension="small"
            id="offset_input"
            name="o"
            type="number"
            onChange={(event): void => setOffset(event.target.value)}
            value={offset}
            placeholder={__('Offset', 'mailpoet')}
          />
        </div>
        <div className="mailpoet-logs-limit">
          <label htmlFor="limit_input" className="screen-reader-text">
            {__('Limit', 'mailpoet')}
          </label>
          <Input
            dimension="small"
            id="limit_input"
            name="l"
            type="number"
            onChange={(event): void => setLimit(event.target.value)}
            value={limit}
            placeholder={__('Limit', 'mailpoet')}
          />
        </div>
        <Button dimension="small" onClick={filterClick}>
          {
            // translators: Button to filter logs
            _x('Filter', 'verb', 'mailpoet')
          }
        </Button>
      </div>

      <table className="mailpoet-listing-table widefat striped" role="grid">
        <thead>
          <tr>
            <th>{__('Name', 'mailpoet')}</th>
            <th>{__('Message', 'mailpoet')}</th>
            <th>{__('Action', 'mailpoet')}</th>
            <th>{__('Created On', 'mailpoet')}</th>
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
