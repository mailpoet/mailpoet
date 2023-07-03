type CellProps = {
  value: number | string | JSX.Element;
  subValue?: number | string;
  className?: string;
};
export function Cell({ value, subValue, className }: CellProps): JSX.Element {
  const mainElement = (
    <div className={`mailpoet-analytics-main-value ${className ?? ''}`}>
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
