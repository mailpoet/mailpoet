import React from 'react';
import classnames from 'classnames';
import {
  Button,
} from '@wordpress/components';

import { check } from '@wordpress/icons';

type Props = {
  isActive: boolean;
  label: string;
  info: string|undefined;
  onToggle: () => any;
}

const FeatureToggle = ({
  isActive,
  label,
  info,
  onToggle,
}: Props) => {
  let content: any;
  if (info) {
    content = (
      <span className="components-menu-item__info-wrapper">
        {label}
        <span className="components-menu-item__info">{info}</span>
      </span>
    );
  } else {
    content = label;
  }

  return (
    <Button
      className={classnames(
        'components-button',
        'components-menu-item__button',
        { 'mailpoet-dropdown-button': !isActive }
      )}
      role="menuitemcheckbox"
      icon={isActive && check}
      onClick={onToggle}
      text={info}
    >
      {content}
    </Button>
  );
};

export default FeatureToggle;
