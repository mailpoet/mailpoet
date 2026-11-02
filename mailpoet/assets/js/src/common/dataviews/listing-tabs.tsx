import classnames from 'classnames';

import type { ListingGroup } from './types';

type Props = {
  tabs: ListingGroup[];
  active: string;
  onSelect: (name: string) => void;
  className?: string;
};

export function ListingTabs({ tabs, active, onSelect, className }: Props) {
  return (
    <div
      className={classnames(
        'mailpoet-categories mailpoet-dataviews__tabs',
        className,
      )}
    >
      <div className="components-tab-panel__tabs" role="tablist">
        {tabs.map((entry) => (
          <a
            key={entry.name}
            href="#"
            role="tab"
            aria-selected={entry.name === active}
            className={classnames(
              'components-button',
              'components-tab-panel__tabs-item',
              `mailpoet-dataviews-group-${entry.name}`,
              { 'is-active': entry.name === active },
            )}
            data-automation-id={`filters_${entry.name}`}
            onClick={(event) => {
              event.preventDefault();
              onSelect(entry.name);
            }}
          >
            <span data-title={entry.label}>{entry.label}</span>
            {Number(entry.count) > 0 && (
              <span className="count">
                {Number(entry.count).toLocaleString()}
              </span>
            )}
          </a>
        ))}
      </div>
    </div>
  );
}
