import React, { useState } from 'react';
import classnames from 'classnames';

import SettingsIcon from './form_placement_options/icons/settings_icon';
import CheckIcon from './form_placement_options/icons/checkbox_icon';

type Props = {
  label: string
  active: boolean
  displaySettingsIcon?: boolean
  canBeActive?: boolean
  onClick: () => void
  className?: string
  children: React.ReactNode
  automationId?: string
}

const SelectionItem = ({
  label,
  active,
  canBeActive,
  onClick,
  children,
  className,
  automationId,
  displaySettingsIcon,
}: Props) => {
  const [hover, setHover] = useState(false);
  return (
    <div
      key={label}
      data-automation-id={automationId}
      className={
        classnames(
          className,
          'selection-item',
          { 'selection-item-active': active && canBeActive }
        )
      }
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
      onClick={onClick}
      onKeyDown={(event) => {
        if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))
        ) {
          event.preventDefault();
          onClick();
        }
      }}
      role="button"
      tabIndex={0}
    >
      <div>
        <div className="selection-item-settings">
          {
            displaySettingsIcon
              ? (
                <div
                  className={
                    classnames(
                      'selection-item-icon',
                      { 'selection-item-icon-hover': hover }
                    )
                  }
                >
                  {SettingsIcon}
                </div>
              )
              : (<div />)
          }
          {
            hover && !active && canBeActive
            && <div className="selection-item-settings-oval" />
          }
          {
            active && canBeActive
            && (
              <div className="selection-item-check">
                {CheckIcon}
              </div>
            )
          }
        </div>
        {children}
      </div>
      {
        hover
        && <div className="selection-item-overlay" />
      }
    </div>
  );
};

SelectionItem.defaultProps = {
  canBeActive: true,
  displaySettingsIcon: true,
  className: undefined,
  automationId: undefined,
};

export default SelectionItem;
