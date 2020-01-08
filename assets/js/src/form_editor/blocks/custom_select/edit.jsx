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
import formatLabel from '../label_formatter.jsx';

import CustomFieldSettings from '../custom_radio/custom_field_settings.jsx';

const CustomSelectEdit = ({ attributes, setAttributes, clientId }) => {
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
            mandatory={attributes.mandatory}
            values={attributes.values}
            isSaving={isSaving}
            onSave={(params) => saveCustomField({
              customFieldId: attributes.customFieldId,
              data: {
                params: {
                  required: params.mandatory ? '1' : undefined,
                  values: params.values.map((value) => ({
                    value: value.name,
                    is_checked: value.isChecked ? '1' : undefined,
                  })),
                },
              },
              onFinish: () => setAttributes({
                mandatory: params.mandatory,
                values: params.values,
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
            label={MailPoet.I18n.t('displayLabelWithinInput')}
            checked={attributes.labelWithinInput}
            onChange={(labelWithinInput) => (setAttributes({ labelWithinInput }))}
          />
        </PanelBody>
      </Panel>
    </InspectorControls>
  );

  const getInput = () => {
    let defaultValue = attributes.labelWithinInput ? formatLabel(attributes) : '-';
    const options = [{
      label: defaultValue,
    }];

    if (Array.isArray(attributes.values) || !attributes.values.length) {
      attributes.values.forEach((value) => {
        options.push({
          label: value.name,
        });
        if (!attributes.labelWithinInput && value.isChecked) {
          defaultValue = value.name;
        }
      });
    }

    return (
      <select className="mailpoet_select" id={clientId} value={defaultValue} readOnly>
        {
          options.map((option) => (
            <option key={option.label} value={option.label}>
              {option.label}
            </option>
          ))
        }
      </select>
    );
  };

  return (
    <>
      {inspectorControls}
      <div className="mailpoet_custom_select">
        {!attributes.labelWithinInput ? (
          <label className="mailpoet_select_label" htmlFor={clientId}>
            {formatLabel(attributes)}
          </label>
        ) : null}
        {getInput()}
      </div>
    </>
  );
};

CustomSelectEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    values: PropTypes.arrayOf(PropTypes.shape({
      name: PropTypes.string.isRequired,
      isChecked: PropTypes.bool,
      id: PropTypes.string.isRequired,
    })),
    mandatory: PropTypes.bool.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
  clientId: PropTypes.string.isRequired,
};

export default CustomSelectEdit;
