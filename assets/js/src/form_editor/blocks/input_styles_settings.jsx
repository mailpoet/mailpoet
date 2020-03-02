import React from 'react';
import MailPoet from 'mailpoet';
import {
  Panel,
  PanelBody,
} from '@wordpress/components';

const InputStylesSettings = () => (
  <Panel>
    <PanelBody title={MailPoet.I18n.t('formSettingsStyles')} initialOpen={false}>
      TODO: styles settings
    </PanelBody>
  </Panel>
);

export default InputStylesSettings;
