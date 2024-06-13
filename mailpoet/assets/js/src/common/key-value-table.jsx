import PropTypes from 'prop-types';

function KeyValueTable({ rows, max_width: maxWidth = 'auto' }) {
  return (
    <table className="widefat fixed" style={{ maxWidth }}>
      <tbody>
        {rows.map((row) => (
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

export { KeyValueTable };
