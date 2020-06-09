import React from 'react';

import { useDispatch, useSelect } from '@wordpress/data';
import { partial } from 'lodash';
import BasicSettingsPanel from './basic_settings_panel.jsx';
import StylesSettingsPanel from './styles_settings_panel.jsx';
import FormPlacementPanel from './form_placement_panel.jsx';
import CustomCssPanel from './custom_css_panel.jsx';

export type FormSettingsType = {
  inputPadding: number,
};

export default () => {
  const { toggleSidebarPanel } = useDispatch('mailpoet-form-editor');
  const openedPanels = useSelect(
    (select) => select('mailpoet-form-editor').getSidebarOpenedPanels(),
    []
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
};
