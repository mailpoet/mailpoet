import { useLayoutEffect } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import generateColumnSelection from './generate_column_selection.jsx';
import matchColumns from './match_columns.jsx';

const MAX_SUBSCRIBERS_SHOWN = 10;

function ColumnDataMatch({ header, subscribers }) {
  const matchedColumnTypes = matchColumns(subscribers, header);
  return (
    <tr>
      <th>{MailPoet.I18n.t('matchData')}</th>
      {matchedColumnTypes.map((columnType, i) => (
        <th
          // eslint-disable-next-line react/no-array-index-key
          key={columnType.column_id + i}
        >
          {/* eslint-disable-next-line jsx-a11y/control-has-associated-label */}
          <select
            className="mailpoet_subscribers_column_data_match"
            data-column-id={columnType.column_id}
            data-column-index={i}
            id={`column_${i}`}
          />
        </th>
      ))}
    </tr>
  );
}
ColumnDataMatch.propTypes = {
  subscribers: PropTypes.arrayOf(
    // all subscribers
    PropTypes.arrayOf(
      // single subscribers
      PropTypes.oneOfType(
        // properties of a subscriber
        [PropTypes.string, PropTypes.number],
      ),
    ),
  ).isRequired,
  header: PropTypes.arrayOf(PropTypes.string),
};

ColumnDataMatch.defaultProps = {
  header: [],
};

function Header({ header }) {
  return (
    <tr className="mailpoet_header">
      <td />
      {header.map((headerName) => (
        <td key={headerName}>{headerName}</td>
      ))}
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
      {subscriber.map((field, i) => (
        <td
          /* eslint-disable-next-line react/no-array-index-key */
          key={`${field}-${index}-${i}`}
        >
          {field}
        </td>
      ))}
    </>
  );
}
Subscriber.propTypes = {
  subscriber: PropTypes.arrayOf(
    PropTypes.oneOfType(
      // properties of a subscriber
      [PropTypes.string, PropTypes.number],
    ),
  ).isRequired,
  index: PropTypes.node.isRequired,
};

function Subscribers({ subscribers, subscribersCount }) {
  const filler = '. . .';
  const fillerArray = Array(subscribers[0].length).fill(filler);
  return (
    <>
      {subscribers.slice(0, MAX_SUBSCRIBERS_SHOWN).map((subscriber, i) => (
        // eslint-disable-next-line react/no-array-index-key
        <tr key={`${subscriber[0]}-${i}`}>
          <Subscriber subscriber={subscriber} index={i + 1} />
        </tr>
      ))}
      {subscribersCount > MAX_SUBSCRIBERS_SHOWN + 1 ? (
        <tr key="filler">
          <Subscriber subscriber={fillerArray} index={filler} />
        </tr>
      ) : null}
      {subscribersCount > MAX_SUBSCRIBERS_SHOWN ? (
        <tr key={subscribers[subscribersCount - 1][0]}>
          <Subscriber
            subscriber={subscribers[subscribersCount - 1]}
            index={subscribersCount}
          />
        </tr>
      ) : null}
    </>
  );
}
Subscribers.propTypes = {
  subscribersCount: PropTypes.number.isRequired,
  subscribers: PropTypes.arrayOf(
    // all subscribers
    PropTypes.arrayOf(
      // single subscribers
      PropTypes.oneOfType(
        // properties of a subscriber
        [PropTypes.string, PropTypes.number],
      ),
    ),
  ).isRequired,
};

function MatchTable({ subscribersCount, subscribers, header }) {
  useLayoutEffect(() => {
    generateColumnSelection();
  });

  return (
    <div className="subscribers_data">
      <table className="mailpoet_subscribers widefat fixed">
        <thead>
          <ColumnDataMatch header={header} subscribers={subscribers} />
        </thead>
        <tbody>
          {header ? <Header header={header} /> : null}
          <Subscribers
            subscribers={subscribers}
            subscribersCount={subscribersCount}
          />
        </tbody>
      </table>
    </div>
  );
}

MatchTable.propTypes = {
  subscribersCount: PropTypes.number,
  subscribers: PropTypes.arrayOf(
    // all subscribers
    PropTypes.arrayOf(
      // single subscribers
      PropTypes.oneOfType(
        // properties of a subscriber
        [PropTypes.string, PropTypes.number],
      ),
    ),
  ),
  header: PropTypes.arrayOf(PropTypes.string),
};

MatchTable.defaultProps = {
  subscribersCount: 0,
  subscribers: [],
  header: [],
};

export default MatchTable;
