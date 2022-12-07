import { useDispatch, useSelect } from '@wordpress/data';
import { partial } from 'lodash';
import { ErrorBoundary } from 'common';
import { BasicSettingsPanel } from './basic_settings_panel';
import { StylesSettingsPanel } from './styles_settings_panel';
import { FormPlacementPanel } from './form_placement_panel';
import { CustomCssPanel } from './custom_css_panel';
import { TagsPanel } from './tags_panel';

export function FormSettings(): JSX.Element {
  const dispatchResult = useDispatch('mailpoet-form-editor');
  const toggleSidebarPanel: (t1: string, ...ts: []) => void =
    dispatchResult.toggleSidebarPanel;
  const openedPanels = useSelect(
    (select) => select('mailpoet-form-editor').getSidebarOpenedPanels(),
    [],
  );

  return (
    <>
      <ErrorBoundary>
        <BasicSettingsPanel
          isOpened={openedPanels.includes('basic-settings')}
          onToggle={partial(toggleSidebarPanel, 'basic-settings')}
        />
      </ErrorBoundary>
      <StylesSettingsPanel
        isOpened={openedPanels.includes('styles-settings')}
        onToggle={partial(toggleSidebarPanel, 'styles-settings')}
      />
      <FormPlacementPanel
        isOpened={openedPanels.includes('form-placement')}
        onToggle={partial(toggleSidebarPanel, 'form-placement')}
      />
      <TagsPanel
        isOpened={openedPanels.includes('tags')}
        onToggle={partial(toggleSidebarPanel, 'tags')}
      />
      <CustomCssPanel
        isOpened={openedPanels.includes('custom-css')}
        onToggle={partial(toggleSidebarPanel, 'custom-css')}
      />
    </>
  );
}
