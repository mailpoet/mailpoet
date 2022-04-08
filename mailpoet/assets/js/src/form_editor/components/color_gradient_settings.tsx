import ColorGradientControl from '@wordpress/block-editor/build-module/components/colors-gradients/control';
import { useSetting } from '@wordpress/block-editor';

type Props = {
  name: string;
  colorValue: string | undefined;
  gradientValue: string | undefined;
  onColorChange: (value: string | undefined) => void;
  onGradientChange: (value: string | undefined) => void;
};

function ColorGradientSettings({
  name,
  colorValue,
  gradientValue,
  onColorChange,
  onGradientChange,
}: Props): JSX.Element {
  const settingsColors = useSetting('color.palette');
  const settingsGradients = useSetting('color.gradients');
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
        className="mailpoet-color-gradient-picker block-editor-panel-color-gradient-settings"
      />
    </div>
  );
}

export default ColorGradientSettings;
