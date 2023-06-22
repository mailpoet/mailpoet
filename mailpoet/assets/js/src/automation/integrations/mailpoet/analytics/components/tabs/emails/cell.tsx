type CellProps = {
  value: number | string | JSX.Element;
  subValue?: number | string;
  badge?: string;
  badgeType?: string;
  className?: string;
};
export function Cell({
  value,
  subValue,
  badge,
  badgeType,
  className,
}: CellProps): JSX.Element {
  const badgeElement = badge ? (
    <span className="mailpoet-analytics-badge">{badge}</span>
  ) : null;
  const mainElement = (
    <div
      className={`mailpoet-analytics-main-value ${className ?? ''} ${
        badgeType ?? ''
      }`}
    >
      {badgeElement}
      {value}
    </div>
  );
  const empty = <>&nbsp;</>;
  return (
    <div className="mailpoet-automation-analytics-emails-table-cell">
      {mainElement}
      <p className="mailpoet-automation-analytics-table-subvalue">
        {subValue}
        {(subValue === undefined || subValue.toString().length === 0) && empty}
      </p>
    </div>
  );
}
