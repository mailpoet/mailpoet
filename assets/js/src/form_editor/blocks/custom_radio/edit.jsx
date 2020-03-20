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

const CustomRadioEdit = ({ attributes, setAttributes, clientId }) => {
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
            mandatory={attributes.mandatory}
            values={attributes.values}
            isSaving={isSaving}
            onSave={(params) => saveCustomField({
              customFieldId: attributes.customFieldId,
              data: {
                params: mapCustomFieldFormData('radio', params),
              },
              onFinish: () => setAttributes({
                mandatory: params.mandatory,
                values: params.values,
              }),
            })}
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
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            data-automation-id="settings_custom_text_label_input"
            onChange={(label) => (setAttributes({ label }))}
          />
          <ToggleControl
            label={MailPoet.I18n.t('displayLabel')}
            checked={!attributes.hideLabel}
            onChange={(hideLabel) => (setAttributes({ hideLabel: !hideLabel }))}
          />
        </PanelBody>
      </Panel>
    </InspectorControls>
  );

  const getLabel = () => {
    if (attributes.hideLabel) return null;
    return formatLabel(attributes);
  };

  return (
    <ParagraphEdit className={attributes.className}>
      {inspectorControls}
      <span className="mailpoet_radio_label">{getLabel()}</span>
      {Array.isArray(attributes.values) && attributes.values.map((value) => (
        <div key={value.id}>
          <label>
            <input
              type="radio"
              disabled
              checked={value.isChecked || false}
              className="mailpoet_radio"
            />
            {value.name}
          </label>
        </div>
      ))}
    </ParagraphEdit>
  );
};

CustomRadioEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    customFieldId: PropTypes.number.isRequired,
    values: PropTypes.arrayOf(PropTypes.shape({
      name: PropTypes.string.isRequired,
      id: PropTypes.string.isRequired,
      isChecked: PropTypes.bool,
    })),
    mandatory: PropTypes.bool.isRequired,
    hideLabel: PropTypes.bool,
    className: PropTypes.string,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
  clientId: PropTypes.string.isRequired,
};

export default CustomRadioEdit;
