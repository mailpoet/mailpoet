import { useDispatch, useSelect } from '@wordpress/data';
import { partial } from 'lodash';
import BasicSettingsPanel from './basic_settings_panel';
import StylesSettingsPanel from './styles_settings_panel';
import FormPlacementPanel from './form_placement_panel';
import CustomCssPanel from './custom_css_panel';

export default function FormSettings(): JSX.Element {
  const dispatchResult = useDispatch('mailpoet-form-editor');
  const toggleSidebarPanel: (t1: string, ...ts: []) => void =
    dispatchResult.toggleSidebarPanel;
  const openedPanels = useSelect(
    (select) => select('mailpoet-form-editor').getSidebarOpenedPanels(),
    [],
  );

  return (
    <>
      <BasicSettingsPanel
        isOpened={openedPanels.includes('basic-settings')}
        onToggle={partial(toggleSidebarPanel, 'basic-settings')}
      />
      <StylesSettingsPanel
        isOpened={openedPanels.includes('styles-settings')}
        onToggle={partial(toggleSidebarPanel, 'styles-settings')}
      />
      <FormPlacementPanel
        isOpened={openedPanels.includes('form-placement')}
        onToggle={partial(toggleSidebarPanel, 'form-placement')}
      />
      <CustomCssPanel
        isOpened={openedPanels.includes('custom-css')}
        onToggle={partial(toggleSidebarPanel, 'custom-css')}
      />
    </>
  );
}
