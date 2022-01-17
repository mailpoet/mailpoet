import React from 'react';
import {
  ColorPalette,
  BaseControl,
} from '@wordpress/components';
import { useSetting } from '@wordpress/block-editor';

type Props = {
  name: string;
  value: string|undefined;
  onChange: (value: string|undefined) => void;
}

const ColorSettings: React.FunctionComponent<Props> = ({
  name,
  value,
  onChange,
}: Props) => {
  const settingsColors = useSetting('color.palette');
  return (
    <div>
      <BaseControl.VisualLabel>
        {name}
      </BaseControl.VisualLabel>
      <ColorPalette
        label={name}
        value={value}
        onChange={onChange}
        colors={settingsColors}
        className="block-editor-panel-color-gradient-settings"
      />
    </div>
  );
};

export default ColorSettings;
