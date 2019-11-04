import React from 'react';
import {
  Panel,
  PanelBody,
} from '@wordpress/components';
import MailPoet from 'mailpoet';

export default () => (
  <>
    <Panel>
      <PanelBody title={MailPoet.I18n.t('formSettings')}>
        <p>TODO Basic Settings</p>
      </PanelBody>
    </Panel>
    <Panel>
      <PanelBody title={MailPoet.I18n.t('formPlacement')} initialOpen={false}>
        <p>TODO Form placement</p>
      </PanelBody>
    </Panel>
    <Panel>
      <PanelBody title={MailPoet.I18n.t('customCss')} initialOpen={false}>
        <p>TODO Custom CSS</p>
      </PanelBody>
    </Panel>
  </>
);
