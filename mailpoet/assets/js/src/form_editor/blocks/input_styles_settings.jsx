import { useRef } from 'react';
import MailPoet from 'mailpoet';
import {
  Button,
  Panel,
  PanelBody,
  RangeControl,
  ToggleControl,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { partial } from 'lodash';
import PropTypes from 'prop-types';

import ColorSettings from 'form_editor/components/color_settings';

function InputStylesSettings({ styles, onChange }) {
  const localStylesRef = useRef(styles);
  const localStyles = localStylesRef.current;

  const { applyStylesToAllTextInputs } = useDispatch('mailpoet-form-editor');

  const updateStyles = (property, value) => {
    const updated = { ...localStylesRef.current };
    updated[property] = value;
    onChange(updated);
    localStylesRef.current = updated;
  };

  const updateInheritFromTheme = (newValue) => {
    if (newValue) {
      updateStyles('inheritFromTheme', newValue);
      return;
    }
    const updated = { ...localStylesRef.current };
    updated.backgroundColor = '#ffffff';
    updated.bold = false;
    updated.borderRadius = 0;
    updated.borderSize = 1;
    updated.borderColor = '#313131';
    updated.inheritFromTheme = newValue;
    onChange(updated);
    localStylesRef.current = updated;
  };

  return (
    <Panel className="mailpoet-automation-input-styles-panel">
      <PanelBody
        title={MailPoet.I18n.t('formSettingsStyles')}
        initialOpen={false}
      >
        <div
          className="mailpoet-styles-settings"
          data-automation-id="input_styles_settings"
        >
          <ToggleControl
            label={MailPoet.I18n.t('formSettingsDisplayFullWidth')}
            checked={localStyles.fullWidth}
            onChange={partial(updateStyles, 'fullWidth')}
          />
          <ToggleControl
            label={MailPoet.I18n.t('formSettingsInheritStyleFromTheme')}
            checked={localStyles.inheritFromTheme}
            onChange={updateInheritFromTheme}
            className="mailpoet-automation-inherit-theme-toggle"
          />
          {!localStyles.inheritFromTheme ? (
            <>
              <ColorSettings
                name={MailPoet.I18n.t('formSettingsStylesFontColor')}
                value={localStyles.fontColor}
                onChange={partial(updateStyles, 'fontColor')}
              />
              <ColorSettings
                name={MailPoet.I18n.t('formSettingsStylesBackgroundColor')}
                value={localStyles.backgroundColor}
                onChange={partial(updateStyles, 'backgroundColor')}
              />
              <ToggleControl
                label={MailPoet.I18n.t('formSettingsBold')}
                checked={localStyles.bold || false}
                onChange={partial(updateStyles, 'bold')}
                className="mailpoet-automation-styles-bold-toggle"
              />
              <RangeControl
                label={MailPoet.I18n.t('formSettingsBorderSize')}
                value={
                  localStyles.borderSize === undefined
                    ? 1
                    : localStyles.borderSize
                }
                min={0}
                max={10}
                allowReset
                onChange={partial(updateStyles, 'borderSize')}
                className="mailpoet-automation-styles-border-size"
              />
              <RangeControl
                label={MailPoet.I18n.t('formSettingsBorderRadius')}
                value={
                  localStyles.borderRadius === undefined
                    ? 1
                    : localStyles.borderRadius
                }
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
          <div>
            <Button
              isPrimary
              onClick={() => applyStylesToAllTextInputs(localStyles)}
              data-automation-id="styles_apply_to_all"
            >
              {MailPoet.I18n.t('formSettingsApplyToAll')}
            </Button>
          </div>
        </div>
      </PanelBody>
    </Panel>
  );
}

export const inputStylesPropTypes = PropTypes.shape({
  fullWidth: PropTypes.bool.isRequired,
  inheritFromTheme: PropTypes.bool.isRequired,
  bold: PropTypes.bool,
  backgroundColor: PropTypes.string,
  borderSize: PropTypes.number,
  borderRadius: PropTypes.number,
  borderColor: PropTypes.string,
});

InputStylesSettings.propTypes = {
  styles: inputStylesPropTypes.isRequired,
  onChange: PropTypes.func.isRequired,
};

export { InputStylesSettings };
