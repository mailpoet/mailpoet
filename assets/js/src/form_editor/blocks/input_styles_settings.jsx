import React, { useRef } from 'react';
import MailPoet from 'mailpoet';
import {
  Button,
  ColorIndicator,
  ColorPalette,
  Panel,
  PanelBody,
  RangeControl,
  ToggleControl,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { partial } from 'lodash';
import PropTypes from 'prop-types';

const InputStylesSettings = ({
  styles,
  onChange,
}) => {
  const localStylesRef = useRef(styles);
  const localStyles = localStylesRef.current;

  const { settingsColors } = useSelect(
    (select) => {
      const { getSettings } = select('core/block-editor');
      return {
        settingsColors: getSettings().colors,
      };
    },
    []
  );

  const { applyStylesToAllTextInputs } = useDispatch('mailpoet-form-editor');

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
              <div>
                <h3 className="mailpoet-styles-settings-heading">
                  {MailPoet.I18n.t('formSettingsStylesBackgroundColor')}
                  {
                    localStyles.backgroundColor !== undefined
                    && (
                      <ColorIndicator
                        colorValue={localStyles.backgroundColor}
                      />
                    )
                  }
                </h3>
                <ColorPalette
                  value={localStyles.backgroundColor}
                  onChange={partial(updateStyles, 'backgroundColor')}
                  colors={settingsColors}
                />
              </div>
              <ToggleControl
                label={MailPoet.I18n.t('formSettingsBold')}
                checked={localStyles.bold || false}
                onChange={partial(updateStyles, 'bold')}
                className="mailpoet-automation-styles-bold-toggle"
              />
              <RangeControl
                label={MailPoet.I18n.t('formSettingsBorderSize')}
                value={localStyles.borderSize === undefined ? 1 : localStyles.borderSize}
                min={0}
                max={10}
                allowReset
                onChange={partial(updateStyles, 'borderSize')}
                className="mailpoet-automation-styles-border-size"
              />
              <RangeControl
                label={MailPoet.I18n.t('formSettingsBorderRadius')}
                value={localStyles.borderRadius === undefined ? 1 : localStyles.borderRadius}
                min={0}
                max={40}
                allowReset
                onChange={partial(updateStyles, 'borderRadius')}
              />
              <div>
                <h3 className="mailpoet-styles-settings-heading">
                  {MailPoet.I18n.t('formSettingsBorderColor')}
                  {
                    localStyles.borderColor !== undefined
                    && (
                      <ColorIndicator
                        colorValue={localStyles.borderColor}
                      />
                    )
                  }
                </h3>
                <ColorPalette
                  value={localStyles.borderColor}
                  onChange={partial(updateStyles, 'borderColor')}
                  colors={settingsColors}
                />
              </div>
            </>
          ) : null}
          <div>
            <Button isPrimary onClick={() => applyStylesToAllTextInputs(localStyles)} data-automation-id="styles_apply_to_all">
              {MailPoet.I18n.t('formSettingsApplyToAll')}
            </Button>
          </div>
        </div>
      </PanelBody>
    </Panel>
  );
};

export const inputStylesPropTypes = PropTypes.shape({
  fullWidth: PropTypes.bool.isRequired,
  inheritFromTheme: PropTypes.bool.isRequired,
  bold: PropTypes.bool,
});

InputStylesSettings.propTypes = {
  styles: inputStylesPropTypes.isRequired,
  onChange: PropTypes.func.isRequired,
};

export { InputStylesSettings };
