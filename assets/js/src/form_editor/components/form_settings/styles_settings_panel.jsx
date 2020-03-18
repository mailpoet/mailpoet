import React, { useRef } from 'react';
import {
  Panel,
  PanelBody,
} from '@wordpress/components';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import { useSelect, useDispatch } from '@wordpress/data';
import { partial } from 'lodash';

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
