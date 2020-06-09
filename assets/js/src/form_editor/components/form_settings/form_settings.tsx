import React from 'react';

import { useDispatch, useSelect } from '@wordpress/data';
import { partial } from 'lodash';
import BasicSettingsPanel from './basic_settings_panel';
import StylesSettingsPanel from './styles_settings_panel';
import FormPlacementPanel from './form_placement_panel';
import CustomCssPanel from './custom_css_panel';

type PlacementStyles = {
  width: {
    unit: string
    value: number
  }
}

export type FormSettingsType = {
  alignment: string
  backgroundImageDisplay?: string
  backgroundImageUrl?: string
  belowPostStyles: PlacementStyles
  borderColor?: string
  borderRadius: number
  borderSize: number
  errorValidationColor?: string
  fixedBarFormDelay: number
  fixedBarFormPosition: string
  fixedBarStyles: PlacementStyles
  fontFamily?: string
  formPadding: number
  inputPadding: number
  otherStyles: PlacementStyles
  placeFixedBarFormOnAllPages: boolean
  placeFixedBarFormOnAllPosts: boolean
  placeFormBellowAllPages: boolean
  placeFormBellowAllPosts: boolean
  placePopupFormOnAllPages: boolean
  placePopupFormOnAllPosts: boolean
  placeSlideInFormOnAllPages: boolean
  placeSlideInFormOnAllPosts: boolean
  popupFormDelay: number
  popupStyles: PlacementStyles
  segments: Array<string>
  slideInFormDelay: number
  slideInFormPosition: string
  slideInStyles: PlacementStyles
  successValidationColor?: string
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
