import React from 'react';
import {
  Panel,
  PanelBody,
  TextControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

import CustomFieldSettings from './custom_field_settings.jsx';

const CustomDateEdit = ({ attributes, setAttributes }) => {
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
            defaultToday={attributes.defaultToday}
            dateFormat={attributes.dateFormat}
            dateType={attributes.dateType}
            isSaving={isSaving}
            onSave={(params) => saveCustomField({
              customFieldId: attributes.customFieldId,
              data: {
                params: {
                  required: params.mandatory ? '1' : undefined,
                  date_type: params.dateType,
                  date_format: params.dateFormat,
                  is_default_today: params.defaultToday,
                },
              },
              onFinish: () => setAttributes({
                mandatory: params.mandatory,
                dateType: params.dateType,
                dateFormat: params.dateFormat,
                dateDefaultToday: params.defaultToday,
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
            data-automation-id="settings_custom_date_label_input"
            onChange={(label) => (setAttributes({ label }))}
          />
        </PanelBody>
      </Panel>
    </InspectorControls>
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
      <label className="mailpoet_text_label" data-automation-id="editor_custom_text_label" htmlFor="custom_text">
        {getLabel()}
        <br />
        <input
          id="custom_date"
          className="mailpoet_date"
          type="text"
          name="custom_date"
          disabled
          data-automation-id="editor_custom_date_input"
        />
      </label>
    </>
  );
};

CustomDateEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    dateFormat: PropTypes.string.isRequired,
    dateType: PropTypes.string.isRequired,
    defaultToday:  PropTypes.bool.isRequired,
    mandatory: PropTypes.bool.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default CustomDateEdit;
