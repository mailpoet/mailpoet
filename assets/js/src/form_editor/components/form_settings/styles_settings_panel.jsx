import {
  Panel,
  PanelBody,
} from '@wordpress/components';
import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

const BasicSettingsPanel = ({ onToggle, isOpened }) => {
  return (
    <Panel>
      <PanelBody title={MailPoet.I18n.t('formSettingsStyles')} opened={isOpened} onToggle={onToggle}>
        Styles
      </PanelBody>
    </Panel>
  );
};

BasicSettingsPanel.propTypes = {
  onToggle: PropTypes.func.isRequired,
  isOpened: PropTypes.bool.isRequired,
};

export default BasicSettingsPanel;
