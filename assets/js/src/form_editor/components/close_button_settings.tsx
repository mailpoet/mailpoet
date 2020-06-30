import React from 'react';

import SelectionItem from './form_settings/selection_item';

type CloseButtonProps = {
  label: string
  active: boolean
  onClick: () => any
  icon: string
}

const CloseButton = ({
  label,
  active,
  onClick,
  icon,
}: CloseButtonProps) => (
  <SelectionItem
    label={label}
    onClick={onClick}
    active={active}
    canBeActive
    className="close-button-selection-item"
  >
    <div>{icon}</div>
  </SelectionItem>
);


type Props = {
  name: string
  value?: number|undefined
  onChange: (value: string|undefined) => any
}

const CloseButtonsSettings = ({
  name,
  value,
  onChange,
}: Props) => (
  <div>
    <h3 className="mailpoet-styles-settings-heading">
      {name}
    </h3>
    <div className="close-button-selection-item-list">
      <CloseButton
        label="kjk1"
        active={false}
        icon="abc"
        onClick={() => onChange('abcv')}
      />
      <CloseButton
        label="kjk2"
        active
        icon="def"
        onClick={() => onChange('abcv')}
      />
      <CloseButton
        label="kjk3"
        active={false}
        icon="gha"
        onClick={() => onChange('abcv')}
      />
      <CloseButton
        label="kjk4"
        active={false}
        icon="ijk"
        onClick={() => onChange('abcv')}
      />
      <CloseButton
        label="kjk5"
        active={false}
        icon="lmn"
        onClick={() => onChange('abcv')}
      />
    </div>
  </div>
);

export {
  CloseButtonsSettings,
  CloseButton,
};
