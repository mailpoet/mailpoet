import React from 'react';
import {
  Panel,
  PanelBody,
  TextControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const SegmentSelectEdit = ({ attributes, setAttributes }) => {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            data-automation-id="settings_first_name_label_input"
            onChange={(label) => (setAttributes({ label }))}
          />
        </PanelBody>
      </Panel>
    </InspectorControls>
  );

  return (
    <>
      {inspectorControls}
      <p>
        {attributes.label}
      </p>
    </>
  );
};

SegmentSelectEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default SegmentSelectEdit;
