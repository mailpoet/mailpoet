type CellProps = {
  value: number | string;
  subValue?: number | string;
  link?: string;
  badge?: string;
  badgeType?: string;
  className?: string;
}
export function Cell({value, subValue, link, badge, badgeType, className}: CellProps): JSX.Element {

  const badgeElement = badge ? <span className={`mailpoet-analytics-badge`}>{badge}</span> : null
  const mainElement = link === undefined ?
    <p className={`mailpoet-analytics-main-value ${className??''} ${badgeType??''}`}>
      {badgeElement}
      {value}
    </p> :
    <p className={`mailpoet-analytics-main-value ${badgeType??''}`}>
      {badgeElement}
      <a href={link}>{value}</a>
    </p>


  return (
    <div className="mailpoet-automation-analytics-emails-table-cell">
      {mainElement}
      <p className="mailpoet-automation-analytics-table-subvalue">{subValue}&nbsp;</p>
    </div>
  )
}
