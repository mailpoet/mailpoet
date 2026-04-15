import {
  // eslint-disable-next-line @typescript-eslint/naming-convention -- WordPress experimental API naming convention
  __experimentalPanelColorGradientSettings as PanelColorGradientSettings,
  useSettings,
} from '@wordpress/block-editor';
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
