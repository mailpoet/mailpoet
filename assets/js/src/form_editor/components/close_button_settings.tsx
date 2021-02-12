import React from 'react';
import { useSelect } from '@wordpress/data';

import SelectionItem from './form_settings/selection_item';

type CloseButtonProps = {
  label: string;
  active: boolean;
  onClick: () => void;
  iconUrl: string;
}

const CloseButton: React.FunctionComponent<CloseButtonProps> = ({
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
  name: string;
  value?: string|undefined;
  onChange: (value: string|undefined) => void;
}

const CloseButtonsSettings: React.FunctionComponent<Props> = ({
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
          label="round_white"
          active={value === 'round_white'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/round_white.svg')}
          onClick={(): void => onChange('round_white')}
        />
        <CloseButton
          label="round_black"
          active={value === 'round_black'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/round_black.svg')}
          onClick={(): void => onChange('round_black')}
        />
        <CloseButton
          label="square_white"
          active={value === 'square_white'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/square_white.svg')}
          onClick={(): void => onChange('square_white')}
        />
        <CloseButton
          label="square_black"
          active={value === 'square_black'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/square_black.svg')}
          onClick={(): void => onChange('square_black')}
        />
        <CloseButton
          label="classic"
          active={value === 'classic'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/classic.svg')}
          onClick={(): void => onChange('classic')}
        />
        <CloseButton
          label="classic_white"
          active={value === 'classic_white'}
          iconUrl={closeIconsUrl.replace('img/form_close_icon', 'img/form_close_icon/classic_white.svg')}
          onClick={(): void => onChange('classic_white')}
        />
      </div>
    </div>
  );
};

export {
  CloseButtonsSettings,
  CloseButton,
};
