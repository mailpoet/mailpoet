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

const CustomCheckboxEdit = ({ attributes, setAttributes }) => {
  const isSaving = useSelect(
    (sel) => sel('mailpoet-form-editor').getIsCustomFieldSaving(),
    []
  );
  const { saveCustomField } = useDispatch('mailpoet-form-editor');

  const getCheckboxLabel = () => {
    if (Array.isArray(attributes.values)) {
      const value = attributes.values[0];
      if (value) {
        return value.name;
      }
    }
    return '';
  };

  const isChecked = () => {
    let checked = false;
    if (Array.isArray(attributes.values)) {
      const value = attributes.values[0];
      if (value && value.isChecked) {
        checked = true;
      }
    }
    return checked;
  };

  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('customFieldSettings')} initialOpen>
          <CustomFieldSettings
            mandatory={attributes.mandatory}
            isSaving={isSaving}
            onSave={(params) => saveCustomField({
              customFieldId: attributes.customFieldId,
              data: {
                params: {
                  required: params.mandatory ? '1' : undefined,
                },
              },
              onFinish: () => setAttributes({
                mandatory: params.mandatory,
              }),
            })}
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
          <input
            type="checkbox"
            checked={isChecked()}
            onChange={(event) => setAttributes({
              values: [{
                name: getCheckboxLabel(),
                checked: !!event.target.checked,
              }],
            })}
          />
          <input
            value={getCheckboxLabel()}
            onChange={(event) => setAttributes({
              values: [{
                name: event.target.value,
                checked: isChecked(),
              }],
            })}
          />
        </PanelBody>
      </Panel>
    </InspectorControls>
  );

  const getLabel = () => {
    if (attributes.hideLabel || !attributes.label) return null;
    return attributes.label;
  };


  let checkboxLabel = getCheckboxLabel();
  if (attributes.mandatory) {
    checkboxLabel += ' *';
  }
  return (
    <>
      {inspectorControls}
      <p>{getLabel()}</p>
      <div>
        <label>
          <input
            type="checkbox"
            disabled
            checked={isChecked()}
          />
          {checkboxLabel}
        </label>
      </div>
    </>
  );
};

CustomCheckboxEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    mandatory: PropTypes.bool.isRequired,
    hideLabel: PropTypes.bool,
    values: PropTypes.arrayOf(PropTypes.shape({
      name: PropTypes.string.isRequired,
      isChecked: PropTypes.bool,
    })),
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default CustomCheckboxEdit;
