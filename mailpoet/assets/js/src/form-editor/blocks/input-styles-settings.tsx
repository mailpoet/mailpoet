import { useRef, useState } from 'react';
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
import { ColorGradientSettings } from '../components/color-gradient-settings';
import { storeName } from '../store/constants';

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

  const { applyStylesToAllTextInputs } = useDispatch(storeName);

  const updateStyles = (property: string, value: unknown) => {
    const updated = { ...localStylesRef.current, [property]: value };
    localStylesRef.current = updated;
    setLocalStyles(updated);
    void onChange(updated);
  };

  const updateInheritFromTheme = (newValue: boolean) => {
    if (newValue) {
      updateStyles('inheritFromTheme', newValue);
      return;
    }
    const updated: InputStyles = {
      ...localStylesRef.current,
      backgroundColor: '#ffffff',
      bold: false,
      borderRadius: 0,
      borderSize: 1,
      borderColor: '#313131',
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
                className="mailpoet-automation-styles-border-radius-size"
              />
            </>
          ) : null}
          <div>
            <Button
              variant="primary"
              onClick={() =>
                void (applyStylesToAllTextInputs(localStyles) as Promise<void>)
              }
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
