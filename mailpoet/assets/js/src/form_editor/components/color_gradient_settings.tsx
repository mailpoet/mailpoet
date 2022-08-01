import PanelColorGradientSettings from '@wordpress/block-editor/build-module/components/colors-gradients/panel-color-gradient-settings';
import { useSetting } from '@wordpress/block-editor';

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

export function ColorGradientSettings({ title, settings }: Props): JSX.Element {
  const settingsColors = useSetting('color.palette');
  const settingsGradients = useSetting('color.gradients');
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
