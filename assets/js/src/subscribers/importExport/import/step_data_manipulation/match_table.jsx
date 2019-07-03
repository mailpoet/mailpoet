import React, { useLayoutEffect } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import _ from 'underscore';

import generateColumnSelection from './generate_column_selection.jsx';
import matchColumns from './match_columns.jsx';

const MAX_SUBSCRIBERS_SHOWN = 10;

function MatchTable({
  subscribersCount,
  subscribers,
  header,
}) {
  let selectedColumns = [];

  useLayoutEffect(() => {
    generateColumnSelection((selectedOptionId, columnIndex) => {
      selectedColumns[columnIndex] = selectedOptionId;
    });
  });

  function ColumnDataMatch() {
    const matchedColumnTypes = matchColumns(subscribers, header);
    selectedColumns = _.pluck(matchedColumnTypes, 'column_id');
    return (
      <tr>
        <th>{MailPoet.I18n.t('matchData')}</th>
        {
          matchedColumnTypes.map((columnType, i) => {
            return (
              // eslint-disable-next-line react/no-array-index-key
              <th key={columnType.column_id + i}>
                <select
                  className="mailpoet_subscribers_column_data_match"
                  data-column-id={columnType.column_id}
                  data-validation-rule="false"
                  data-column-index={i}
                  id={`column_${i}`}
                />
              </th>
            );
          })
        }
      </tr>
    );
  }

  function Header() {
    return (
      <tr className="mailpoet_header">
        <td />
        {header.map(headerName => <td key={headerName}>{headerName}</td>)}
      </tr>
    );
  }

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

  function Subscribers() {
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
                <Subscriber
                  subscriber={subscribers[subscribersCount - 1]}
                  index={subscribersCount}
                />
              </tr>
            )
            : null
        }
      </>
    );
  }

  return (
    <div className="subscribers_data">
      <table className="mailpoet_subscribers widefat fixed">
        <thead>
          <ColumnDataMatch />
        </thead>
        <tbody>
          <Header />
          <Subscribers />
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
