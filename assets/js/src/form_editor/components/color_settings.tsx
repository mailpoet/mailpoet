import React from 'react';
import {
  ColorIndicator,
  ColorPalette,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

type Props = {
  name: string,
  value: string|undefined
  onChange: (value: string|undefined) => any
}

const ColorSettings = ({
  name,
  value,
  onChange,
}: Props) => {
  const { settingsColors } = useSelect(
    (select) => {
      const { getSettings } = select('core/block-editor');
      return {
        settingsColors: getSettings().colors,
      };
    },
    []
  );
  return (
    <div>
      <h3 className="mailpoet-styles-settings-heading">
        {name}
        {
          value !== undefined
          && (
            <ColorIndicator
              colorValue={value}
            />
          )
        }
      </h3>
      <ColorPalette
        value={value}
        onChange={onChange}
        colors={settingsColors}
      />
    </div>
  );
};

export default ColorSettings;
