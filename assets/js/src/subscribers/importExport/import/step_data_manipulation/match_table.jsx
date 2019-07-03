import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import matchColumns from './match_columns.jsx';

const MAX_SUBSCRIBERS_SHOWN = 10;

function ColumnDataMatch({ columnTypes }) {
  return (
    <tr>
      <th>{MailPoet.I18n.t('matchData')}</th>
      {/* eslint-disable-next-line react/no-array-index-key */}
      {columnTypes.map((columnType, i) => <th key={columnType.column_id + i}>{columnType.column_id}</th>)}
    </tr>
  );
}

ColumnDataMatch.propTypes = {
  columnTypes: PropTypes.arrayOf(PropTypes.shape({ column_id: PropTypes.string })).isRequired,
};

function Header({ header }) {
  return (
    <tr className="mailpoet_header">
      <td />
      {header.map(headerName => <td key={headerName}>{headerName}</td>)}
    </tr>
  );
}

Header.propTypes = {
  header: PropTypes.arrayOf(PropTypes.string).isRequired,
};

function Subscriber({ subscriber, index }) {
  return (
    <>
      <td>{index}</td>
      {/* eslint-disable-next-line react/no-array-index-key */}
      {subscriber.map((field, i) => <td key={field + index + i}>{field}</td>)}
    </>
  );
}

Subscriber.propTypes = {
  subscriber: PropTypes.arrayOf(PropTypes.string).isRequired,
  index: PropTypes.node.isRequired,
};

function Subscribers({ subscribers, subscribersCount }) {
  const filler = '. . .';
  const fillerArray = Array(subscribers[0].length).fill(filler);
  return (
    <>
      {
        subscribers
          .slice(0, MAX_SUBSCRIBERS_SHOWN)
          .map((subscriber, i) => (
            <tr key={subscriber[0]}>
              <Subscriber subscriber={subscriber} index={i + 1} />
            </tr>
          ))
      }
      {
        subscribersCount > MAX_SUBSCRIBERS_SHOWN + 1
          ? <tr key="filler"><Subscriber subscriber={fillerArray} index={filler} /></tr>
          : null
      }
      {
        subscribersCount > MAX_SUBSCRIBERS_SHOWN
          ? (
            <tr key={subscribers[subscribersCount - 1][0]}>
              <Subscriber subscriber={subscribers[subscribersCount - 1]} index={subscribersCount} />
            </tr>
          )
          : null
      }
    </>
  );
}

Subscribers.propTypes = {
  subscribersCount: PropTypes.number.isRequired,
  subscribers: PropTypes.arrayOf(PropTypes.arrayOf(PropTypes.string)).isRequired,
};

function MatchTable({
  subscribersCount,
  subscribers,
  header,
}) {
  const matchedColumnTypes = matchColumns(subscribers, header);

  return (
    <div className="subscribers_data">
      <table className="mailpoet_subscribers widefat fixed">
        <thead>
          <ColumnDataMatch columnTypes={matchedColumnTypes} />
        </thead>
        <tbody>
          <Header header={header} />
          <Subscribers subscribers={subscribers} subscribersCount={subscribersCount} />
        </tbody>
      </table>
    </div>
  );
}

MatchTable.propTypes = {
  subscribersCount: PropTypes.number,
  subscribers: PropTypes.arrayOf(PropTypes.arrayOf(PropTypes.string)),
  header: PropTypes.arrayOf(PropTypes.string),
};

MatchTable.defaultProps = {
  subscribersCount: 0,
  subscribers: [],
  header: [],
};

export default MatchTable;
