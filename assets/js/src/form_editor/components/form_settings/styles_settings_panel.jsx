import React, { useRef } from 'react';
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
import FontSizeSettings from 'form_editor/components/font_size_settings';

const BasicSettingsPanel = ({ onToggle, isOpened }) => {
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');
  const settings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const settingsRef = useRef(settings);
  const updateStyles = (property, value) => {
    const updated = { ...settingsRef.current };
    updated[property] = value;
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
          <ColorSettings
            name={MailPoet.I18n.t('formSettingsStylesBackgroundColor')}
            value={settings.backgroundColor}
            onChange={partial(updateStyles, 'backgroundColor')}
          />
          <ColorSettings
            name={MailPoet.I18n.t('formSettingsStylesFontColor')}
            value={settings.fontColor}
            onChange={partial(updateStyles, 'fontColor')}
          />
          <FontSizeSettings
            name={MailPoet.I18n.t('formSettingsStylesFontSize')}
            value={settings.fontSize}
            onChange={partial(updateStyles, 'fontSize')}
          />
          <RangeControl
            label={MailPoet.I18n.t('formSettingsInputPadding')}
            value={settings.inputPadding !== undefined ? settings.inputPadding : 5}
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
            value={settings.borderRadius !== undefined ? settings.borderRadius : 0}
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
              { value: HorizontalAlignment.Left, label: MailPoet.I18n.t('formSettingsAlignmentLeft') },
              { value: HorizontalAlignment.Center, label: MailPoet.I18n.t('formSettingsAlignmentCenter') },
              { value: HorizontalAlignment.Right, label: MailPoet.I18n.t('formSettingsAlignmentRight') },
            ]}
            value={settings.alignment !== undefined ? settings.alignment : 'left'}
          />
          <RangeControl
            label={MailPoet.I18n.t('formSettingsFormPadding')}
            value={settings.formPadding !== undefined ? settings.formPadding : 20}
            min={0}
            max={40}
            allowReset
            onChange={(value) => {
              updateStyles('formPadding', value !== undefined ? value : 20);
            }}
          />
        </div>
      </PanelBody>
    </Panel>
  );
};

BasicSettingsPanel.propTypes = {
  onToggle: PropTypes.func.isRequired,
  isOpened: PropTypes.bool.isRequired,
};

export default BasicSettingsPanel;
