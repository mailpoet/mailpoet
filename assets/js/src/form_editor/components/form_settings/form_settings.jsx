import React from 'react';

import BasicSettingsPanel from './basic_settings_panel.jsx';
import FormPlacementPanel from './form_placement_panel.jsx';
import CustomCssPanel from './custom_css_panel.jsx';

export default () => (
  <>
    <BasicSettingsPanel />
    <FormPlacementPanel />
    <CustomCssPanel />
  </>
);
