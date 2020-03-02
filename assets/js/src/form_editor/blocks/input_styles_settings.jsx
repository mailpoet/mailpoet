import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import {
  Panel,
  PanelBody,
  ToggleControl,
} from '@wordpress/components';
import { partial } from 'underscore';
import PropTypes from 'prop-types';

const InputStylesSettings = ({
  styles,
  onChange,
}) => {
  const [localStyles, setStyles] = useState(styles);

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
