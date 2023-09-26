import { useRef } from 'react';
import { MailPoet } from 'mailpoet';
import {
  Panel,
  PanelBody,
  RangeControl,
  ToggleControl,
} from '@wordpress/components';
import { partial } from 'lodash';

import { ColorGradientSettings } from 'form_editor/components/color_gradient_settings';
import { FontSizeSettings } from 'form_editor/components/font_size_settings';
import { InputBlockStyles } from 'form_editor/store/form_data_types';
import { FontFamilySettings } from '../../components/font_family_settings';

type Props = {
  styles: InputBlockStyles;
  onChange: (styles: InputBlockStyles) => void;
  formInputPadding: number;
  formFontFamily?: string;
};

function StylesSettings({
  styles,
  onChange,
  formInputPadding,
  formFontFamily,
}: Props): JSX.Element {
  const localStylesRef = useRef(styles);
  const localStyles = localStylesRef.current;

  const updateStyles = (property, value): void => {
    const updated = { ...localStylesRef.current };
    updated[property] = value;
    onChange(updated);
    localStylesRef.current = updated;
  };

  const updateInheritFromTheme = (newValue: boolean): void => {
    if (newValue) {
      updateStyles('inheritFromTheme', newValue);
      return;
    }
    const updated = { ...localStylesRef.current };
    updated.backgroundColor = '#eeeeee';
    updated.bold = false;
    updated.borderRadius = 0;
    updated.borderSize = 1;
    updated.borderColor = '#313131';
    updated.fontColor = '#313131';
    updated.fontSize = undefined;
    updated.padding = formInputPadding;
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
                    label: MailPoet.I18n.t('formSettingsStylesBackground'),
                    colorValue: styles.backgroundColor,
                    gradientValue: styles.gradient,
                    onColorChange: partial(updateStyles, 'backgroundColor'),
                    onGradientChange: partial(updateStyles, 'gradient'),
                  },
                  {
                    label: MailPoet.I18n.t('formSettingsStylesFont'),
                    colorValue: styles.fontColor,
                    onColorChange: partial(updateStyles, 'fontColor'),
                  },
                  {
                    label: MailPoet.I18n.t('formSettingsBorder'),
                    colorValue: localStyles.borderColor,
                    onColorChange: partial(updateStyles, 'borderColor'),
                  },
                ]}
              />
              <FontSizeSettings
                value={styles.fontSize}
                onChange={partial(updateStyles, 'fontSize')}
              />
              <ToggleControl
                label={MailPoet.I18n.t('formSettingsBold')}
                checked={localStyles.bold || false}
                onChange={partial(updateStyles, 'bold')}
                className="mailpoet-automation-styles-bold-toggle"
              />
              <FontFamilySettings
                name={MailPoet.I18n.t('formSettingsStylesFontFamily')}
                value={styles.fontFamily || formFontFamily}
                onChange={partial(updateStyles, 'fontFamily')}
              />
              <RangeControl
                label={MailPoet.I18n.t('formSettingsInputPadding')}
                value={
                  localStyles.padding !== undefined
                    ? localStyles.padding
                    : formInputPadding
                }
                min={0}
                max={30}
                allowReset
                onChange={partial(updateStyles, 'padding')}
              />
              <RangeControl
                label={MailPoet.I18n.t('formSettingsBorderSize')}
                value={
                  localStyles.borderSize !== undefined
                    ? localStyles.borderSize
                    : 1
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
                  localStyles.borderRadius !== undefined
                    ? localStyles.borderRadius
                    : 0
                }
                min={0}
                max={40}
                allowReset
                onChange={partial(updateStyles, 'borderRadius')}
              />
            </>
          ) : null}
        </div>
      </PanelBody>
    </Panel>
  );
}

export { StylesSettings };
