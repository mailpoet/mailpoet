import { SelectionItem } from '../selection-item';

type Props = {
  label: string;
  icon: JSX.Element;
  active: boolean;
  canBeActive?: boolean;
  onClick: () => void;
};

function FormPlacementOption({
  label,
  icon,
  active,
  canBeActive = true,
  onClick,
}: Props): JSX.Element {
  return (
    <SelectionItem
      label={label}
      onClick={onClick}
      active={active}
      canBeActive={canBeActive}
      className="form-placement-option"
      automationId={`form-placement-option-${label}`}
    >
      <div className="form-placement-option-icon">{icon}</div>
      <div className="form-placement-option-label">
        <p>{label}</p>
      </div>
    </SelectionItem>
  );
}

export { FormPlacementOption };
