import React from 'react';
import { useSelect } from '@wordpress/data';

import SelectionItem from './form_settings/selection_item';

type CloseButtonProps = {
  label: string
  active: boolean
  onClick: () => any
  iconUrl: string
}

const CloseButton = ({
  label,
  active,
  onClick,
  iconUrl,
}: CloseButtonProps) => (
  <SelectionItem
    label={label}
    onClick={onClick}
    active={active}
    canBeActive
    displaySettingsIcon={false}
    className="close-button-selection-item"
  >
    <img
      src={iconUrl}
      alt={label.replace('_', ' ')}
      className="close-button-selection-item-icon"
    />
  </SelectionItem>
);

type Props = {
  name: string
  value?: string|undefined
  onChange: (value: string|undefined) => any
}

const CloseButtonsSettings = ({
  name,
  value,
  onChange,
}: Props) => {
  const closeIconsUrl = useSelect(
    (sel) => sel('mailpoet-form-editor').getCloseIconsUrl(),
    []
  );
  return (
    <div>
      <h3 className="mailpoet-styles-settings-heading">
        {name}
      </h3>
      <div className="close-button-selection-item-list">
        <CloseButton
          label="classic"
          active={value === 'classic'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/classic.svg')}
          onClick={() => onChange('classic')}
        />
        <CloseButton
          label="round_black"
          active={value === 'round_black'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/round_black.svg')}
          onClick={() => onChange('round_black')}
        />
        <CloseButton
          label="round_white"
          active={value === 'round_white'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/round_white.svg')}
          onClick={() => onChange('round_white')}
        />
        <CloseButton
          label="square_black"
          active={value === 'square_black'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/square_black.svg')}
          onClick={() => onChange('square_black')}
        />
        <CloseButton
          label="square_white"
          active={value === 'square_white'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/square_white.svg')}
          onClick={() => onChange('square_white')}
        />
      </div>
    </div>
  );
};

export {
  CloseButtonsSettings,
  CloseButton,
};
