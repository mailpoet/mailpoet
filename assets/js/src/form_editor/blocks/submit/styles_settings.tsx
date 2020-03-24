import React, { useRef } from 'react';
import MailPoet from 'mailpoet';
import {
  Panel,
  PanelBody,
  RangeControl,
  ToggleControl,
} from '@wordpress/components';
import { partial } from 'lodash';

import ColorSettings from 'form_editor/components/color_settings';
import FontSizeSetting from 'form_editor/components/font_size_settings';

type Styles = {
  fullWidth: boolean,
  inheritFromTheme: boolean,
  bold?: boolean,
  backgroundColor?: string,
  fontColor?: string,
  fontSize?: number,
  borderSize?: number,
  borderRadius?: number,
  borderColor?: string,
}

type Props = {
  styles: Styles,
  onChange: (styles: Styles) => any,
}

const StylesSettings = ({
  styles,
  onChange,
}: Props) => {
  const localStylesRef = useRef(styles);
  const localStyles = localStylesRef.current;

  const updateStyles = (property, value) => {
    const updated = { ...localStylesRef.current };
    updated[property] = value;
    onChange(updated);
    localStylesRef.current = updated;
  };

  return (
    <Panel className="mailpoet-automation-input-styles-panel">
      <PanelBody title={MailPoet.I18n.t('formSettingsStyles')} initialOpen={false}>
        <div className="mailpoet-styles-settings" data-automation-id="input_styles_settings">
          <ToggleControl
            label={MailPoet.I18n.t('formSettingsDisplayFullWidth')}
            checked={localStyles.fullWidth}
            onChange={partial(updateStyles, 'fullWidth')}
          />
          <ToggleControl
            label={MailPoet.I18n.t('formSettingsInheritStyleFromTheme')}
            checked={localStyles.inheritFromTheme}
            onChange={partial(updateStyles, 'inheritFromTheme')}
            className="mailpoet-automation-inherit-theme-toggle"
          />
          {!localStyles.inheritFromTheme ? (
            <>
              <ColorSettings
                name={MailPoet.I18n.t('formSettingsStylesBackgroundColor')}
                value={styles.backgroundColor}
                onChange={partial(updateStyles, 'backgroundColor')}
              />
              <ColorSettings
                name={MailPoet.I18n.t('formSettingsStylesFontColor')}
                value={styles.fontColor}
                onChange={partial(updateStyles, 'fontColor')}
              />
              <FontSizeSetting
                name={MailPoet.I18n.t('formSettingsStylesFontSize')}
                value={styles.fontSize}
                onChange={partial(updateStyles, 'fontSize')}
              />
              <ToggleControl
                label={MailPoet.I18n.t('formSettingsBold')}
                checked={localStyles.bold || false}
                onChange={partial(updateStyles, 'bold')}
                className="mailpoet-automation-styles-bold-toggle"
              />
              <RangeControl
                label={MailPoet.I18n.t('formSettingsBorderSize')}
                value={localStyles.borderSize !== undefined ? localStyles.borderSize : 1}
                min={0}
                max={10}
                allowReset
                onChange={partial(updateStyles, 'borderSize')}
                className="mailpoet-automation-styles-border-size"
              />
              <RangeControl
                label={MailPoet.I18n.t('formSettingsBorderRadius')}
                value={localStyles.borderRadius !== undefined ? localStyles.borderRadius : 1}
                min={0}
                max={40}
                allowReset
                onChange={partial(updateStyles, 'borderRadius')}
              />
              <ColorSettings
                name={MailPoet.I18n.t('formSettingsBorderColor')}
                value={localStyles.borderColor}
                onChange={partial(updateStyles, 'borderColor')}
              />
            </>
          ) : null}
        </div>
      </PanelBody>
    </Panel>
  );
};

export default StylesSettings;
