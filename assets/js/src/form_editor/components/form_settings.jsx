import React from 'react';
import {
  Panel,
  PanelBody,
} from '@wordpress/components';

export default () => (
  <>
    <Panel>
      <PanelBody title="Settings">
        <p>TODO Basic Settings</p>
      </PanelBody>
    </Panel>
    <Panel>
      <PanelBody title="From placement" initialOpen={false}>
        <p>TODO Form placement</p>
      </PanelBody>
    </Panel>
    <Panel>
      <PanelBody title="Custom CSS" initialOpen={false}>
        <p>TODO Custom CSS</p>
      </PanelBody>
    </Panel>
  </>
);
