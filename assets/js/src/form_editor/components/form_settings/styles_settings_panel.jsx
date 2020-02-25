import React, { useState } from 'react';
import {
  ColorIndicator,
  ColorPalette,
  Panel,
  PanelBody,
} from '@wordpress/components';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import { useSelect } from '@wordpress/data';

const BasicSettingsPanel = ({ onToggle, isOpened }) => {
  const [selectedColor, setSelectedColor] = useState(undefined);
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
              selectedColor !== undefined
              && (
                <ColorIndicator
                  colorValue={selectedColor}
                />
              )
            }
          </span>
          <ColorPalette
            value={selectedColor}
            onChange={setSelectedColor}
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
