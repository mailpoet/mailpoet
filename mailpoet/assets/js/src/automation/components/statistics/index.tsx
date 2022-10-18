type Item = {
  key: string;
  label: string;
  value: number;
};

type Props = {
  items: Item[];
};

export function Statistics({ items }: Props): JSX.Element {
  const intl = new Intl.NumberFormat();
  return (
    <ul className="mailpoet-automation-stats">
      {items.map((item) => (
        <li key={item.key} className="mailpoet-automation-stats-item">
          <span className="mailpoet-automation-stats-label">{item.label}</span>
          {intl.format(item.value)}
        </li>
      ))}
    </ul>
  );
}
