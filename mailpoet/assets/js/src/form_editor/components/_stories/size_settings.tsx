import { action } from '_storybook/action';
import { SizeSettings } from '../size_settings';

export default {
  title: 'FormEditor/Size Settings',
};

export function Settings(): JSX.Element {
  return (
    <>
      <SizeSettings
        label="Basic Size Settings"
        value={{
          unit: 'pixel',
          value: 200,
        }}
        onChange={action('on change no action')}
      />
      <SizeSettings
        label="Size Settings With All Props"
        value={{
          unit: 'pixel',
          value: 200,
        }}
        defaultPercentValue={25}
        defaultPixelValue={150}
        maxPercents={80}
        maxPixels={500}
        minPercents={20}
        minPixels={100}
        onChange={action('on change no action')}
      />
    </>
  );
}

export function SettingsInSidebar(): JSX.Element {
  return (
    <div className="edit-post-sidebar mailpoet_form_editor_sidebar">
      <SizeSettings
        label="Basic Size Settings"
        value={{
          unit: 'pixel',
          value: 200,
        }}
        onChange={action('on change no action')}
      />
      <SizeSettings
        label="Size Settings in %"
        value={{
          unit: 'percent',
          value: 100,
        }}
        maxPercents={100}
        minPercents={10}
        onChange={action('on change no action')}
      />
    </div>
  );
}
