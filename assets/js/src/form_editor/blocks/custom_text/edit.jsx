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

import ParagraphEdit from '../paragraph_edit.jsx';
import CustomFieldSettings from './custom_field_settings.jsx';
import formatLabel from '../label_formatter.jsx';
import mapCustomFieldFormData from '../map_custom_field_form_data.jsx';
import InputStylesSettings from '../input_styles_settings.jsx';

const CustomTextEdit = ({ attributes, setAttributes, clientId }) => {
  const isSaving = useSelect(
    (sel) => sel('mailpoet-form-editor').getIsCustomFieldSaving(),
    []
  );
  const isDeleting = useSelect(
    (sel) => sel('mailpoet-form-editor').getIsCustomFieldDeleting(),
    []
  );

  const {
    saveCustomField,
    deleteCustomField,
    customFieldEdited,
  } = useDispatch('mailpoet-form-editor');

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
                  params: mapCustomFieldFormData('text', params),
                },
                onFinish: () => setAttributes({
                  mandatory: params.mandatory,
                  validate: params.validate,
                }),
              });
            }}
            onCustomFieldDelete={() => deleteCustomField(
              attributes.customFieldId,
              clientId
            )}
            isDeleting={isDeleting}
            onChange={(data, hasUnsavedChanges) => hasUnsavedChanges && customFieldEdited()}
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
      <InputStylesSettings />
    </InspectorControls>
  );

  const getTextInput = (placeholder) => (
    <input
      id={clientId}
      className="mailpoet_text"
      type="text"
      name="custom_text"
      placeholder={placeholder}
      data-automation-id="editor_custom_text_input"
    />
  );

  return (
    <ParagraphEdit>
      {inspectorControls}
      {!attributes.labelWithinInput ? (
        <label className="mailpoet_text_label" data-automation-id="editor_custom_text_label" htmlFor={clientId}>
          {formatLabel(attributes)}
        </label>
      ) : null}
      {getTextInput(attributes.labelWithinInput ? formatLabel(attributes) : '')}
    </ParagraphEdit>
  );
};

CustomTextEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    validate: PropTypes.string,
    labelWithinInput: PropTypes.bool.isRequired,
    mandatory: PropTypes.bool.isRequired,
    customFieldId: PropTypes.number.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
  clientId: PropTypes.string.isRequired,
};

export default CustomTextEdit;
