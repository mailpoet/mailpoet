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
          { 'form-placement-option-active': active && canBeActive }
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
        <div className="form-placement-option-settings">
          <div
            className={
              classnames(
                'form-placement-settings-icon',
                { 'form-placement-settings-icon-hover': hover }
              )
            }
          >
            {SettingsIcon}
          </div>
          {
            hover && !active && canBeActive
            && <div className="form-placement-settings-oval" />
          }
          {
            active && canBeActive
            && (
              <div className="form-placement-settings-check">
                {CheckIcon}
              </div>
            )
          }
        </div>
        <div className="form-placement-option-icon">
          {icon}
        </div>
      </div>
      <div className="form-placement-option-label">
        <p>{label}</p>
      </div>
      {
        hover
        && <div className="form-placement-settings-overlay" />
      }
    </div>
  );
};

FormPlacementOption.defaultProps = {
  canBeActive: true,
};

export default FormPlacementOption;
