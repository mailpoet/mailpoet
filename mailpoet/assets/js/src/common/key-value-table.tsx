type Props = {
  rows: {
    key: string;
    value: string | number | React.ReactNode;
  }[];
  max_width?: string;
};

function KeyValueTable({ rows, max_width: maxWidth = 'auto' }: Props) {
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

export { KeyValueTable };
