import PanelColorGradientSettings from '@wordpress/block-editor/build-module/components/colors-gradients/panel-color-gradient-settings';
import { useSettings } from '@wordpress/block-editor';
import { withBoundary } from 'common';

type Setting = {
  label: string;
  colorValue: string | undefined;
  gradientValue?: string | undefined;
  onColorChange: (value: string | undefined) => void;
  onGradientChange?: (value: string | undefined) => void;
};

type Props = {
  title: string;
  settings: Setting[];
};

function ColorGradientSettings({ title, settings }: Props): JSX.Element {
  const [settingsColors, settingsGradients] = useSettings(
    'color.palette',
    'color.gradients',
  );
  return (
    <div>
      <PanelColorGradientSettings
        title={title}
        colors={settingsColors}
        gradients={settingsGradients}
        settings={settings}
      />
    </div>
  );
}

ColorGradientSettings.displayName = 'ColorGradientSettings';
const ColorGradientSettingsWithBoundary = withBoundary(ColorGradientSettings);
export { ColorGradientSettingsWithBoundary as ColorGradientSettings };
