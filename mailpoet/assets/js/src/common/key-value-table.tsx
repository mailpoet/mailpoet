import classnames from 'classnames';

type Props = {
  rows: {
    key: string;
    value: string | number | React.ReactNode;
    action?: React.ReactNode;
  }[];
  max_width?: string;
  is_fixed?: boolean;
};

function KeyValueTable({
  rows,
  max_width: maxWidth = 'auto',
  is_fixed = true,
}: Props) {
  return (
    <table
      className={classnames('widefat', { fixed: is_fixed })}
      style={{ maxWidth }}
    >
      <tbody>
        {rows.map((row) => (
          <tr key={`row_${row.key}`}>
            <td className="row-title">{row.key}</td>
            <td>{row.value}</td>
            {row.action ? <td>{row.action}</td> : null}
          </tr>
        ))}
      </tbody>
    </table>
  );
}

export { KeyValueTable };
