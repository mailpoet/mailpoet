import { useRef } from 'react';
import { MailPoet } from 'mailpoet';
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
import { ColorGradientSettings } from '../components/color_gradient_settings';

type InputStyles = {
  fullWidth: boolean;
  inheritFromTheme: boolean;
  bold: boolean;
  backgroundColor: string;
  borderSize: number;
  borderRadius: number;
  borderColor: string;
  fontColor: string;
};

type InputStylesSettingsProps = {
  styles: InputStyles;
  onChange: (styles: InputStyles) => void;
};

function InputStylesSettings({ styles, onChange }: InputStylesSettingsProps) {
  const localStylesRef = useRef(styles);
  const localStyles = localStylesRef.current;

  const { applyStylesToAllTextInputs } = useDispatch('mailpoet-form-editor');

  const updateStyles = (property, value) => {
    const updated = { ...localStylesRef.current };
    updated[property] = value;
    void onChange(updated);
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
              <ColorGradientSettings
                title={MailPoet.I18n.t('formSettingsColor')}
                settings={[
                  {
                    label: MailPoet.I18n.t('formSettingsStylesFont'),
                    colorValue: localStyles.fontColor,
                    onColorChange: partial(updateStyles, 'fontColor'),
                  },
                  {
                    label: MailPoet.I18n.t('formSettingsStylesBackground'),
                    colorValue: localStyles.backgroundColor,
                    onColorChange: partial(updateStyles, 'backgroundColor'),
                  },
                  {
                    label: MailPoet.I18n.t('formSettingsBorder'),
                    colorValue: localStyles.borderColor,
                    onColorChange: partial(updateStyles, 'borderColor'),
                  },
                ]}
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

/**
 * @deprecated since removal of propTypes for InputStylesSettings
 * Remove when TextInputEdit is converted to tsx
 */
export const inputStylesPropTypes = PropTypes.shape({
  fullWidth: PropTypes.bool.isRequired,
  inheritFromTheme: PropTypes.bool.isRequired,
  bold: PropTypes.bool,
  backgroundColor: PropTypes.string,
  borderSize: PropTypes.number,
  borderRadius: PropTypes.number,
  borderColor: PropTypes.string,
});

export { InputStylesSettings };
