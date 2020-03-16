import React, { useRef } from 'react';
import {
  ColorIndicator,
  ColorPalette,
  FontSizePicker,
  Panel,
  PanelBody,
} from '@wordpress/components';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import { useSelect, useDispatch } from '@wordpress/data';
import { partial } from 'lodash';

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

  const { settingsColors, fontSizes } = useSelect(
    (select) => {
      const { getSettings } = select('core/block-editor');
      return {
        settingsColors: getSettings().colors,
        fontSizes: getSettings().fontSizes,
      };
    },
    []
  );
  return (
    <Panel>
      <PanelBody
        title={MailPoet.I18n.t('formSettingsStyles')}
        opened={isOpened}
        onToggle={onToggle}
      >
        <div className="mailpoet-styles-settings">
          <div>
            <h3 className="mailpoet-styles-settings-heading">
              {MailPoet.I18n.t('formSettingsStylesBackgroundColor')}
              {
                settings.backgroundColor !== undefined
                && (
                  <ColorIndicator
                    colorValue={settings.backgroundColor}
                  />
                )
              }
            </h3>
            <ColorPalette
              value={settings.backgroundColor}
              onChange={partial(updateStyles, 'backgroundColor')}
              colors={settingsColors}
            />
          </div>

          <div>
            <h3 className="mailpoet-styles-settings-heading">
              {MailPoet.I18n.t('formSettingsStylesFontColor')}
              {
                settings.fontColor !== undefined
                && (
                  <ColorIndicator
                    colorValue={settings.fontColor}
                  />
                )
              }
            </h3>
            <ColorPalette
              value={settings.fontColor}
              onChange={partial(updateStyles, 'fontColor')}
              colors={settingsColors}
            />
          </div>

          <div>
            <h3 className="mailpoet-styles-settings-heading">
              {MailPoet.I18n.t('formSettingsStylesFontSize')}
            </h3>
            <FontSizePicker
              value={settings.fontSize}
              onChange={partial(updateStyles, 'fontSize')}
              fontSizes={fontSizes}
            />
          </div>
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
