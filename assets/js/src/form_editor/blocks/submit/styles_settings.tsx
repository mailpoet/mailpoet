import React, { useRef } from 'react';
import MailPoet from 'mailpoet';
import {
  Panel,
  PanelBody,
  ToggleControl,
} from '@wordpress/components';
import { partial } from 'lodash';

type Styles = {
  fullWidth: boolean,
  inheritFromTheme: boolean,
  bold?: boolean,
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
            <ToggleControl
              label={MailPoet.I18n.t('formSettingsBold')}
              checked={localStyles.bold || false}
              onChange={partial(updateStyles, 'bold')}
              className="mailpoet-automation-styles-bold-toggle"
            />
          ) : null}
        </div>
      </PanelBody>
    </Panel>
  );
};

export default StylesSettings;
