import React from 'react';

type Props = {
  label: string,
  icon: JSX.Element,
}

const FormPlacementOption = ({ label, icon }: Props) => (
  <div className="form-placement-option">
    <div>
      <div className="form-placement-option-settings">
        x
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
