import React from 'react';
import {
  ColorIndicator,
  ColorPalette,
  Panel,
  PanelBody,
} from '@wordpress/components';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import { useSelect, useDispatch } from '@wordpress/data';

const BasicSettingsPanel = ({ onToggle, isOpened }) => {
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');
  const settings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const setBackgroundColor = (color) => {
    changeFormSettings({
      ...settings,
      backgroundColor: color,
    });
  };
  const settingsColors = useSelect(
    (select) => {
      const { getSettings } = select('core/block-editor');
      return getSettings().colors;
    },
    []
  );
  return (
    <Panel>
      <PanelBody title={MailPoet.I18n.t('formSettingsStyles')} opened={isOpened} onToggle={onToggle}>
        <div className="block-editor-panel-color-gradient-settings">
          <span className="components-base-control__label">
            {MailPoet.I18n.t('formSettingsStylesBackgroundColor')}
            {
              settings.backgroundColor !== undefined
              && (
                <ColorIndicator
                  colorValue={settings.backgroundColor}
                />
              )
            }
          </span>
          <ColorPalette
            value={settings.backgroundColor}
            onChange={setBackgroundColor}
            colors={settingsColors}
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
