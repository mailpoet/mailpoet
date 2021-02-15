import React from 'react';
import SelectionItem from '../selection_item';

type Props = {
  label: string;
  icon: JSX.Element;
  active: boolean;
  canBeActive?: boolean;
  onClick: () => void;
}

const FormPlacementOption: React.FunctionComponent<Props> = ({
  label,
  icon,
  active,
  canBeActive,
  onClick,
}: Props) => (
  <SelectionItem
    label={label}
    onClick={onClick}
    active={active}
    canBeActive={canBeActive}
    className="form-placement-option"
    automationId={`form-placement-option-${label}`}
  >
    <div className="form-placement-option-icon">
      {icon}
    </div>
    <div className="form-placement-option-label">
      <p>{label}</p>
    </div>
  </SelectionItem>
);

FormPlacementOption.defaultProps = {
  canBeActive: true,
};

export default FormPlacementOption;
