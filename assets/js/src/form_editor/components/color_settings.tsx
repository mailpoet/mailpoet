import React from 'react';
import {
  ColorIndicator,
  ColorPalette,
} from '@wordpress/components';
import { __experimentalUseEditorFeature } from '@wordpress/block-editor';

type Props = {
  name: string;
  value: string|undefined;
  onChange: (value: string|undefined) => void;
}

const ColorSettings = ({
  name,
  value,
  onChange,
}: Props) => {
  const settingsColors = __experimentalUseEditorFeature('color.palette');
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
