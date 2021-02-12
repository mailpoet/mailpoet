import React from 'react';
import { MenuItem } from '@wordpress/components';

import { check } from '@wordpress/icons';

type Props = {
  isActive: boolean;
  label: string;
  info: string|undefined;
  onToggle: () => void;
}

const FeatureToggle: React.FunctionComponent<Props> = ({
  isActive,
  label,
  info,
  onToggle,
}: Props) => (
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

export default FeatureToggle;
