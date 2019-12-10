import React from 'react';
import {
  Panel,
  PanelBody,
  TextareaControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const CustomHtmlEdit = ({ attributes, setAttributes }) => {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextareaControl
            label={MailPoet.I18n.t('blockCustomHtmlContentLabel')}
            value={attributes.content}
            data-automation-id="settings_custom_html_content"
            rows={4}
            onChange={(content) => setAttributes({ content })}
          />
        </PanelBody>
      </Panel>

    </InspectorControls>
  );
  return (
    <>
      {inspectorControls}
      <div>
        {attributes.content}
      </div>
    </>
  );
};

CustomHtmlEdit.propTypes = {
  attributes: PropTypes.shape({
    content: PropTypes.string.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default CustomHtmlEdit;
