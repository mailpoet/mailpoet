import classnames from 'classnames';

export type Category = {
  name: string;
  label: string;
  count?: number | string;
  automationId?: string;
  className?: string;
};

type Props = Category & {
  onSelect: (name: string) => void;
  active?: boolean;
};

export function CategoriesItem({
  onSelect,
  name,
  label,
  count,
  automationId,
  className,
  active,
}: Props) {
  const classes = classnames(
    'components-button',
    'components-tab-panel__tabs-item',
    className,
    { 'is-active': !!active },
  );

  return (
    <a
      key={name}
      href="#"
      aria-current={active ? 'page' : undefined}
      className={classes}
      onClick={(event) => {
        event.preventDefault();
        onSelect(name);
      }}
      data-automation-id={automationId}
    >
      <span data-title={label}>{label}</span>
      {Number(count) > 0 && (
        <span className="count">
          {parseInt(count.toString(), 10).toLocaleString()}
        </span>
      )}
    </a>
  );
}
