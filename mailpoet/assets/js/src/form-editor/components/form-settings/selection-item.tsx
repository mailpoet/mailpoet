import { ReactNode, useState } from 'react';
import classnames from 'classnames';

import { SettingsIcon } from './form-placement-options/icons/settings-icon';
import { CheckboxIcon } from './form-placement-options/icons/checkbox-icon';

type Props = {
  label: string;
  active: boolean;
  displaySettingsIcon?: boolean;
  canBeActive?: boolean;
  onClick: () => void;
  className?: string;
  children: ReactNode;
  automationId?: string;
};

function SelectionItem({
  label,
  active,
  onClick,
  children,
  canBeActive = true,
  className = undefined,
  automationId = undefined,
  displaySettingsIcon = true,
}: Props): JSX.Element {
  const [hover, setHover] = useState(false);
  return (
    <div
      key={label}
      data-automation-id={automationId}
      className={classnames(className, 'selection-item', {
        'selection-item-active': active && canBeActive,
      })}
      onMouseEnter={(): void => setHover(true)}
      onMouseLeave={(): void => setHover(false)}
      onClick={onClick}
      onKeyDown={(event): void => {
        if (
          ['keydown', 'keypress'].includes(event.type) &&
          ['Enter', ' '].includes(event.key)
        ) {
          event.preventDefault();
          onClick();
        }
      }}
      role="button"
      tabIndex={0}
    >
      <div className="selection-item-body">
        <div className="selection-item-settings">
          {displaySettingsIcon ? (
            <div
              className={classnames('selection-item-icon', {
                'selection-item-icon-hover': hover,
              })}
            >
              {SettingsIcon}
            </div>
          ) : (
            <div />
          )}
          {hover && !active && canBeActive && (
            <div className="selection-item-settings-oval" />
          )}
          {active && canBeActive && (
            <div className="selection-item-check">{CheckboxIcon}</div>
          )}
        </div>
        {children}
      </div>
      {hover && <div className="selection-item-overlay" />}
    </div>
  );
}

export { SelectionItem };
