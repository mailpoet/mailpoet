import React from 'react';

import SettingsIcon from './settings_icon';

type Props = {
  label: string,
  icon: JSX.Element,
}

const FormPlacementOption = ({ label, icon }: Props) => (
  <div className="form-placement-option">
    <div>
      <div className="form-placement-option-settings">
        <div className="form-placement-settings-icon">
          {SettingsIcon}
        </div>
        {/* todo next line only show on hover */}
        <div className="form-placement-settings-oval" />
      </div>
      <div className="form-placement-option-icon">
        {icon}
      </div>
    </div>
    <div className="form-placement-option-label">
      <p>{label}</p>
    </div>
  </div>
);

export default FormPlacementOption;
