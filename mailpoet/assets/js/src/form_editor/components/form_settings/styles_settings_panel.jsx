import { useRef, useEffect } from 'react';
import {
  Panel,
  PanelBody,
  RangeControl,
  SelectControl,
} from '@wordpress/components';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import { useSelect, useDispatch } from '@wordpress/data';
import { partial } from 'lodash';
import HorizontalAlignment from 'common/styles';

import ColorSettings from 'form_editor/components/color_settings';
import ColorGradientSettings from 'form_editor/components/color_gradient_settings';
import FontSizeSettings from 'form_editor/components/font_size_settings';
import ImageSettings from 'form_editor/components/image_settings';
import { CloseButtonsSettings } from 'form_editor/components/close_button_settings';
import { formStyles as defaultFormStyles } from 'form_editor/store/defaults';
import FontFamilySettings from '../font_family_settings';

function StylesSettingsPanel({ onToggle, isOpened }) {
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');
  const settings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const settingsRef = useRef(settings);
  useEffect(() => {
    settingsRef.current = settings;
  }, [settings]);

  const updateStyles = (property, value) => {
    const updated = { ...settingsRef.current };
    updated[property] = value ?? defaultFormStyles[property] ?? undefined;
    changeFormSettings(updated);
    settingsRef.current = updated;
  };

  return (
    <Panel>
      <PanelBody
        title={MailPoet.I18n.t('formSettingsStyles')}
        opened={isOpened}
        onToggle={onToggle}
      >
        <div className="mailpoet-styles-settings">
          <ColorGradientSettings
            name={MailPoet.I18n.t('formSettingsStylesBackgroundColor')}
            colorValue={settings.backgroundColor}
            gradientValue={settings.gradient}
            onColorChange={partial(updateStyles, 'backgroundColor')}
            onGradientChange={partial(updateStyles, 'gradient')}
          />
          <ImageSettings
            name={MailPoet.I18n.t('formSettingsStylesBackgroundImage')}
            imageUrl={settings.backgroundImageUrl}
            onImageUrlChange={partial(updateStyles, 'backgroundImageUrl')}
            imageDisplay={settings.backgroundImageDisplay}
            onImageDisplayChange={partial(
              updateStyles,
              'backgroundImageDisplay',
            )}
          />
          <ColorSettings
            name={MailPoet.I18n.t('formSettingsStylesFontColor')}
            value={settings.fontColor}
            onChange={partial(updateStyles, 'fontColor')}
          />
          <FontSizeSettings
            value={settings.fontSize}
            onChange={partial(updateStyles, 'fontSize')}
          />
          <FontFamilySettings
            name={MailPoet.I18n.t('formSettingsStylesFontFamily')}
            value={settings.fontFamily}
            onChange={partial(updateStyles, 'fontFamily')}
          />
          <RangeControl
            label={MailPoet.I18n.t('formSettingsInputPadding')}
            value={settings.inputPadding}
            min={0}
            max={30}
            allowReset
            onChange={partial(updateStyles, 'inputPadding')}
          />
          <RangeControl
            label={MailPoet.I18n.t('formSettingsBorderSize')}
            value={settings.borderSize !== undefined ? settings.borderSize : 0}
            min={0}
            max={10}
            allowReset
            onChange={partial(updateStyles, 'borderSize')}
            className="mailpoet-automation-styles-border-size"
          />
          <RangeControl
            label={MailPoet.I18n.t('formSettingsBorderRadius')}
            value={
              settings.borderRadius !== undefined ? settings.borderRadius : 0
            }
            min={0}
            max={40}
            allowReset
            onChange={partial(updateStyles, 'borderRadius')}
          />
          <ColorSettings
            name={MailPoet.I18n.t('formSettingsBorderColor')}
            value={settings.borderColor}
            onChange={partial(updateStyles, 'borderColor')}
          />
          <SelectControl
            label={MailPoet.I18n.t('formSettingsAlignment')}
            onChange={partial(updateStyles, 'alignment')}
            options={[
              {
                value: HorizontalAlignment.Left,
                label: MailPoet.I18n.t('formSettingsAlignmentLeft'),
              },
              {
                value: HorizontalAlignment.Center,
                label: MailPoet.I18n.t('formSettingsAlignmentCenter'),
              },
              {
                value: HorizontalAlignment.Right,
                label: MailPoet.I18n.t('formSettingsAlignmentRight'),
              },
            ]}
            value={settings.alignment}
          />
          <RangeControl
            label={MailPoet.I18n.t('formSettingsFormPadding')}
            value={settings.formPadding}
            min={0}
            max={40}
            allowReset
            onChange={partial(updateStyles, 'formPadding')}
          />
          <ColorSettings
            name={MailPoet.I18n.t('successValidationColorTitle')}
            value={settings.successValidationColor}
            onChange={partial(updateStyles, 'successValidationColor')}
          />
          <ColorSettings
            name={MailPoet.I18n.t('errorValidationColorTitle')}
            value={settings.errorValidationColor}
            onChange={partial(updateStyles, 'errorValidationColor')}
          />
          <CloseButtonsSettings
            name={MailPoet.I18n.t('closeButtonHeading')}
            value={settings.closeButton}
            onChange={partial(updateStyles, 'closeButton')}
          />
        </div>
      </PanelBody>
    </Panel>
  );
}

StylesSettingsPanel.propTypes = {
  onToggle: PropTypes.func.isRequired,
  isOpened: PropTypes.bool.isRequired,
};

export default StylesSettingsPanel;
