import React from 'react';
import {
  Panel,
  PanelBody,
} from '@wordpress/components';
import MailPoet from 'mailpoet';

import FormPlacementPanel from './form_placement_panel.jsx';

export default () => (
  <>
    <Panel>
      <PanelBody title={MailPoet.I18n.t('formSettings')}>
        <p>TODO Basic Settings</p>
      </PanelBody>
    </Panel>
    <FormPlacementPanel />
    <Panel>
      <PanelBody title={MailPoet.I18n.t('customCss')} initialOpen={false}>
        <p>TODO Custom CSS</p>
      </PanelBody>
    </Panel>
  </>
);
