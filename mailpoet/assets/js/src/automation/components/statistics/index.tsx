type Item = {
  key: string;
  label: string;
  value: number;
};

type Props = {
  items: Item[];
  labelPosition?: 'before' | 'after';
};

export function Statistics({
  items,
  labelPosition = 'before',
}: Props): JSX.Element {
  const intl = new Intl.NumberFormat();
  return (
    <ul className="mailpoet-automation-stats">
      {items.map((item) => (
        <li key={item.key} className="mailpoet-automation-stats-item">
          <span
            className={`mailpoet-automation-stats-label display-${labelPosition}`}
          >
            {item.label}
          </span>
          {intl.format(item.value)}
        </li>
      ))}
    </ul>
  );
}
