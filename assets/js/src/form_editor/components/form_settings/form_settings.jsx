import React from 'react';
import {
  Panel,
  PanelBody,
} from '@wordpress/components';
import MailPoet from 'mailpoet';

import FormPlacementPanel from './form_placement_panel.jsx';
import CustomCssPanel from './custom_css_panel.jsx';

export default () => (
  <>
    <Panel>
      <PanelBody title={MailPoet.I18n.t('formSettings')}>
        <p>TODO Basic Settings</p>
      </PanelBody>
    </Panel>
    <FormPlacementPanel />
    <CustomCssPanel />
  </>
);
