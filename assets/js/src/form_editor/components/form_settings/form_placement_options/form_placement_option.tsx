import React, { useState } from 'react';
import classnames from 'classnames';

import SettingsIcon from './settings_icon';

type Props = {
  label: string,
  icon: JSX.Element,
  active: boolean,
}

const FormPlacementOption = ({ label, icon, active }: Props) => {
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
            hover
            && <div className="form-placement-settings-oval" />
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
