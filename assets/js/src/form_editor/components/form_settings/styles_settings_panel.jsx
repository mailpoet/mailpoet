import React, { useRef } from 'react';
import {
  FontSizePicker,
  Panel,
  PanelBody,
} from '@wordpress/components';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import { useSelect, useDispatch } from '@wordpress/data';
import { partial } from 'lodash';

import ColorSettings from 'form_editor/components/color_settings';

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

  const fontSizes = useSelect(
    (select) => {
      const { getSettings } = select('core/block-editor');
      return getSettings().fontSizes;
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
