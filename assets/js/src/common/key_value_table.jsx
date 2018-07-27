import React from 'react';

const KeyValueTable = props => (
  <table className={'widefat fixed'} style={{ maxWidth: props.max_width }}>
    <tbody>
      {props.children.map(row => (
        <tr key={`row_${row.key}`}>
          <td className={'row-title'}>{ row.key }</td><td>{ row.value }</td>
        </tr>
      ))}
    </tbody>
  </table>
);

KeyValueTable.propTypes = {
  max_width: React.PropTypes.string,
  children: React.PropTypes.arrayOf(React.PropTypes.shape({
    key: React.PropTypes.string.isRequired,
    value: React.PropTypes.oneOfType([
      React.PropTypes.string,
      React.PropTypes.number,
      React.PropTypes.element,
    ]).isRequired,
  })).isRequired,
};

KeyValueTable.defaultProps = {
  max_width: 'auto',
};

module.exports = KeyValueTable;
