import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import {
  ColorIndicator,
  ColorPalette,
  Panel,
  PanelBody,
  ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { partial } from 'underscore';
import PropTypes from 'prop-types';

const InputStylesSettings = ({
  styles,
  onChange,
}) => {
  const [localStyles, setStyles] = useState(styles);

  const { settingsColors } = useSelect(
    (select) => {
      const { getSettings } = select('core/block-editor');
      return {
        settingsColors: getSettings().colors,
      };
    },
    []
  );

  const updateStyles = (property, value) => {
    const updated = { ...localStyles };
    updated[property] = value;
    onChange(updated);
    setStyles(updated);
  };
  return (
    <Panel>
      <PanelBody title={MailPoet.I18n.t('formSettingsStyles')} initialOpen={false}>
        <ToggleControl
          label={MailPoet.I18n.t('formSettingsDisplayFullWidth')}
          checked={localStyles.fullWidth}
          onChange={partial(updateStyles, 'fullWidth')}
        />
        <ToggleControl
          label={MailPoet.I18n.t('formSettingsInheritStyleFromTheme')}
          checked={localStyles.inheritFromTheme}
          onChange={partial(updateStyles, 'inheritFromTheme')}
        />
        {!localStyles.inheritFromTheme ? (
          <>
            <div>
              <h3 className="mailpoet-styles-settings-heading">
                {MailPoet.I18n.t('formSettingsStylesBackgroundColor')}
                {
                  styles.backgroundColor !== undefined
                  && (
                    <ColorIndicator
                      colorValue={styles.backgroundColor}
                    />
                  )
                }
              </h3>
              <ColorPalette
                value={styles.backgroundColor}
                onChange={partial(updateStyles, 'backgroundColor')}
                colors={settingsColors}
              />
            </div>
            <ToggleControl
              label={MailPoet.I18n.t('formSettingsBold')}
              checked={localStyles.bold || false}
              onChange={partial(updateStyles, 'bold')}
            />
          </>
        ) : null}
      </PanelBody>
    </Panel>
  );
};

export const inputStylesPropTypes = PropTypes.shape({
  fullWidth: PropTypes.bool.isRequired,
  inheritFromTheme: PropTypes.bool.isRequired,
  bold: PropTypes.bool,
});

InputStylesSettings.propTypes = {
  styles: inputStylesPropTypes.isRequired,
  onChange: PropTypes.func.isRequired,
};

export { InputStylesSettings };
