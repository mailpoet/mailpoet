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
import { useDispatch, useSelect } from '@wordpress/data';

import CustomFieldSettings from './custom_field_settings.jsx';

const CustomTextEdit = ({ attributes, setAttributes }) => {
  const isSaving = useSelect(
    (sel) => sel('mailpoet-form-editor').getIsCustomFieldSaving(),
    []
  );
  const { saveCustomField } = useDispatch('mailpoet-form-editor');

  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('customFieldSettings')} initialOpen>
          <CustomFieldSettings
            updateAttributes={(attrs) => (setAttributes(attrs))}
            customFieldId={attributes.customFieldId}
            mandatory={attributes.mandatory}
            validate={attributes.validate}
            isSaving={isSaving}
            onSave={(params) => {
              saveCustomField({
                customFieldId: attributes.customFieldId,
                data: {
                  params: {
                    required: params.mandatory ? '1' : undefined,
                    validate: params.validate,
                  },
                },
                onFinish: () => setAttributes({
                  mandatory: params.mandatory,
                  validate: params.validate,
                }),
              });
            }}
          />
        </PanelBody>
      </Panel>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('customFieldsFormSettings')} initialOpen>
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

  const getTextInput = (placeholder) => (
    <input
      id="custom_text"
      className="mailpoet_text"
      type="text"
      name="custom_text"
      disabled
      placeholder={placeholder}
      data-automation-id="editor_custom_text_input"
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
      {attributes.labelWithinInput ? (getTextInput(getLabel())
      ) : (
        <label className="mailpoet_text_label" data-automation-id="editor_custom_text_label" htmlFor="custom_text">
          {getLabel()}
          <br />
          {getTextInput('')}
        </label>
      )}
    </>
  );
};

CustomTextEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    labelWithinInput: PropTypes.bool.isRequired,
    mandatory: PropTypes.bool.isRequired,
    customFieldId: PropTypes.number.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default CustomTextEdit;
