import PropTypes from 'prop-types';

function KeyValueTable(props) {
  return (
    <table className="widefat fixed" style={{ maxWidth: props.max_width }}>
      <tbody>
        {props.rows.map((row) => (
          <tr key={`row_${row.key}`}>
            <td className="row-title">{row.key}</td>
            <td>{row.value}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

KeyValueTable.propTypes = {
  max_width: PropTypes.string,
  rows: PropTypes.arrayOf(
    PropTypes.shape({
      key: PropTypes.string.isRequired,
      value: PropTypes.oneOfType([
        PropTypes.string,
        PropTypes.number,
        PropTypes.element,
      ]).isRequired,
    }),
  ).isRequired,
};

KeyValueTable.defaultProps = {
  max_width: 'auto',
};

export default KeyValueTable;
