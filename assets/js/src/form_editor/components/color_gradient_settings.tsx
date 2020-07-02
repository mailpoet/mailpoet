import React from 'react';
import ColorGradientControl from '@wordpress/block-editor/build-module/components/colors-gradients/control';
import { useSelect } from '@wordpress/data';

type Props = {
  name: string,
  colorValue: string|undefined
  gradientValue: string|undefined
  onColorChange: (value: string|undefined) => any
  onGradientChange: (value: string|undefined) => any
}

const ColorGradientSettings = ({
  name,
  colorValue,
  gradientValue,
  onColorChange,
  onGradientChange,
}: Props) => {
  const { settingsColors, settingsGradients } = useSelect(
    (select) => {
      const { getSettings } = select('core/block-editor');
      return {
        settingsColors: getSettings().colors,
        settingsGradients: getSettings().gradients,
      };
    },
    []
  );
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
