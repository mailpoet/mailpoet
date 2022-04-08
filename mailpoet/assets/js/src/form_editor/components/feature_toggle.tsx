import { MenuItem } from '@wordpress/components';

import { check } from '@wordpress/icons';

type Props = {
  isActive: boolean;
  label: string;
  info: string | undefined;
  onToggle: () => void;
};

function FeatureToggle({
  isActive,
  label,
  info,
  onToggle,
}: Props): JSX.Element {
  return (
    <MenuItem
      icon={isActive && check}
      isSelected={isActive}
      onClick={onToggle}
      role="menuitemcheckbox"
      info={info}
    >
      {label}
    </MenuItem>
  );
}

export default FeatureToggle;
