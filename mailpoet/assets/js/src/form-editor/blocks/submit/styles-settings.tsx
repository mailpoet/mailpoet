import { useRef, useState } from 'react';
import { MailPoet } from 'mailpoet';
import {
  Panel,
  PanelBody,
  RangeControl,
  ToggleControl,
} from '@wordpress/components';
import { partial } from 'lodash';

import { ColorGradientSettings } from 'form-editor/components/color-gradient-settings';
import { FontSizeSettings } from 'form-editor/components/font-size-settings';
import { InputBlockStyles } from 'form-editor/store/form-data-types';
import { FontFamilySettings } from '../../components/font-family-settings';

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
  // useState triggers re-renders (needed for RangeControl value propagation
  // in @wordpress/components v29+), while useRef provides stale-closure-safe
  // reads when multiple updates fire in the same event cycle.
  const [localStyles, setLocalStyles] = useState(styles);
  const localStylesRef = useRef(localStyles);
  localStylesRef.current = localStyles;

  const prevStylesRef = useRef(styles);
  if (styles !== prevStylesRef.current) {
    prevStylesRef.current = styles;
    setLocalStyles(styles);
  }

  const updateStyles = (property: string, value: unknown): void => {
    const updated = { ...localStyles, [property]: value };
    localStylesRef.current = updated;
    setLocalStyles(updated);
    onChange(updated);
  };

  const updateInheritFromTheme = (newValue: boolean): void => {
    if (newValue) {
      updateStyles('inheritFromTheme', newValue);
      return;
    }
    const updated: InputBlockStyles = {
      ...localStylesRef.current,
      backgroundColor: '#eeeeee',
      bold: false,
      borderRadius: 0,
      borderSize: 1,
      borderColor: '#313131',
      fontColor: '#313131',
      fontSize: undefined,
      padding: formInputPadding,
      inheritFromTheme: newValue,
    };
    localStylesRef.current = updated;
    setLocalStyles(updated);
    onChange(updated);
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
                value={localStyles.padding ?? formInputPadding}
                min={0}
                max={30}
                allowReset
                resetFallbackValue={formInputPadding}
                onChange={(value: number | undefined) =>
                  updateStyles('padding', value ?? formInputPadding)
                }
              />
              <RangeControl
                label={MailPoet.I18n.t('formSettingsBorderSize')}
                value={localStyles.borderSize ?? 1}
                min={0}
                max={10}
                allowReset
                resetFallbackValue={1}
                onChange={(value: number | undefined) =>
                  updateStyles('borderSize', value ?? 1)
                }
                className="mailpoet-automation-styles-border-size"
              />
              <RangeControl
                label={MailPoet.I18n.t('formSettingsBorderRadius')}
                value={localStyles.borderRadius ?? 0}
                min={0}
                max={40}
                allowReset
                resetFallbackValue={0}
                onChange={(value: number | undefined) =>
                  updateStyles('borderRadius', value ?? 0)
                }
              />
            </>
          ) : null}
        </div>
      </PanelBody>
    </Panel>
  );
}

export { StylesSettings };
