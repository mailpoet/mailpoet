import React, { useState } from 'react';
import classnames from 'classnames';

import SettingsIcon from './settings_icon';
import CheckIcon from './checkbox_icon';

type Props = {
  label: string,
  icon: JSX.Element,
  active: boolean,
  onClick: () => void,
}

const FormPlacementOption = ({
  label,
  icon,
  active,
  onClick,
}: Props) => {
  const [hover, setHover] = useState(false);
  return (
    <div
      className={
        classnames(
          'form-placement-option',
          { 'form-placement-option-active': active }
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
            hover && !active
            && <div className="form-placement-settings-oval" />
          }
          {
            active
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

export default FormPlacementOption;
