import classNames from 'classnames';

export type Category = {
  name: string;
  label: string;
  count?: number | string;
  automationId?: string;
};

type Props = Category & {
  onSelect: (name: string) => void;
  active?: boolean;
};

function CategoriesItem({
  onSelect,
  name,
  label,
  count,
  automationId,
  active,
}: Props) {
  const classes = classNames('mailpoet-categories-item', { active: !!active });

  return (
    <a
      key={name}
      href="#"
      className={classes}
      onClick={(event) => {
        event.preventDefault();
        onSelect(name);
      }}
      data-automation-id={automationId}
    >
      <span className="mailpoet-categories-title" data-title={label}>
        {label}
      </span>
      {count > 0 && (
        <span className="mailpoet-categories-count">
          {parseInt(count.toString(), 10).toLocaleString()}
        </span>
      )}
    </a>
  );
}

export default CategoriesItem;
