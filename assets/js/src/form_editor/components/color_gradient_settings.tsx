import React from 'react';
import ColorGradientControl from '@wordpress/block-editor/build-module/components/colors-gradients/control';
import { __experimentalUseEditorFeature } from '@wordpress/block-editor';

type Props = {
  name: string;
  colorValue: string|undefined;
  gradientValue: string|undefined;
  onColorChange: (value: string|undefined) => void;
  onGradientChange: (value: string|undefined) => void;
}

const ColorGradientSettings = ({
  name,
  colorValue,
  gradientValue,
  onColorChange,
  onGradientChange,
}: Props) => {
  const settingsColors = __experimentalUseEditorFeature('color.palette');
  const settingsGradients = __experimentalUseEditorFeature('color.gradients');
  return (
    <div>
      <ColorGradientControl
        colorValue={colorValue}
        gradientValue={gradientValue}
        onColorChange={onColorChange}
        onGradientChange={onGradientChange}
        colors={settingsColors}
        gradients={settingsGradients}
        label={name}
        className="mailpoet-color-gradient-picker"
      />
    </div>
  );
};

export default ColorGradientSettings;
