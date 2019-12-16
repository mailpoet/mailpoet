import React from 'react';
import {
  Panel,
  PanelBody,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import CustomFieldSettings from './custom_field_settings.jsx';

const CustomTextAreaEdit = ({ attributes, setAttributes }) => {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('customFieldSettings')} initialOpen>
          <CustomFieldSettings
            updateAttributes={(attrs) => (setAttributes(attrs))}
            customFieldId={attributes.customFieldId}
            mandatory={attributes.mandatory}
            validate={attributes.validate}
          />
        </PanelBody>
      </Panel>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            data-automation-id="settings_custom_text_label_input"
            onChange={(label) => (setAttributes({ label }))}
          />
          <ToggleControl
            label={MailPoet.I18n.t('displayLabelWithinInput')}
            checked={attributes.labelWithinInput}
            onChange={(labelWithinInput) => (setAttributes({ labelWithinInput }))}
          />
        </PanelBody>
      </Panel>

    </InspectorControls>
  );

  const getTextArea = (placeholder) => (
    <textarea
      id="custom_text"
      className="mailpoet_text"
      name="custom_text"
      disabled
      data-automation-id="editor_custom_text_input"
      value={placeholder}
      rows={attributes.lines}
    />
  );

  const getLabel = () => {
    if (attributes.mandatory) {
      return `${attributes.label} *`;
    }
    return attributes.label;
  };

  return (
    <>
      {inspectorControls}
      {attributes.labelWithinInput ? (getTextArea(getLabel())
      ) : (
        <label className="mailpoet_text_label" data-automation-id="editor_custom_text_label" htmlFor="custom_text">
          {getLabel()}
          <br />
          {getTextArea('')}
        </label>
      )}
    </>
  );
};

CustomTextAreaEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    labelWithinInput: PropTypes.bool.isRequired,
    mandatory: PropTypes.bool.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default CustomTextAreaEdit;
