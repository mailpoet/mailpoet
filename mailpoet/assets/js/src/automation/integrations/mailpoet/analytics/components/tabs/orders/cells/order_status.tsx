export function OrderStatusCell({
  id,
  name,
}: {
  id: string;
  name: string;
}): JSX.Element {
  return (
    <span
      className={`mailpoet-analytics-order-status mailpoet-analytics-order-status-${id}`}
    >
      {name}
    </span>
  );
}
