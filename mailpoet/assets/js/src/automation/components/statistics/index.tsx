import { Fragment } from '@wordpress/element';
import { locale } from '../../config';

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
  const intl = new Intl.NumberFormat(locale.toString());
  return (
    <div className="mailpoet-automation-stats">
      {items.map((item, i) => (
        <Fragment key={item.key}>
          <div key={item.key} className="mailpoet-automation-stats-item">
            <span
              className={`mailpoet-automation-stats-label display-${labelPosition}`}
            >
              {item.label}
            </span>
            <span className="mailpoet-automation-stats-value">
              {intl.format(item.value)}
            </span>
          </div>

          {i < items.length - 1 && (
            <div className="mailpoet-automation-stats-item-separator">›</div>
          )}
        </Fragment>
      ))}
    </div>
  );
}
