import React, { useState } from 'react';
import classnames from 'classnames';

import SettingsIcon from './icons/settings_icon';
import CheckIcon from './icons/checkbox_icon';

type Props = {
  label: string,
  icon: JSX.Element,
  active: boolean,
  canBeActive?: boolean,
  onClick: () => void,
}

const FormPlacementOption = ({
  label,
  icon,
  active,
  canBeActive,
  onClick,
}: Props) => {
  const [hover, setHover] = useState(false);
  return (
    <div
      key={label}
      data-automation-id={`form-placement-option-${label}`}
      className={
        classnames(
          'form-placement-option',
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
        <div className="form-placement-option-icon">
          {icon}
        </div>
        <div className="form-placement-option-label">
          <p>{label}</p>
        </div>
      </div>
      {
        hover
        && <div className="selection-item-overlay" />
      }
    </div>
  );
};

FormPlacementOption.defaultProps = {
  canBeActive: true,
};

export default FormPlacementOption;
