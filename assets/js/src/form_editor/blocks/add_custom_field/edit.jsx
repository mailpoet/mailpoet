import React, { useState } from 'react';
import PropTypes from 'prop-types';
import {
  Placeholder,
  Button,
  SelectControl,
  TextControl,
} from '@wordpress/components';
import { BlockIcon } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import MailPoet from 'mailpoet';

import icon from './icon.jsx';
import mapCustomFieldFormData from '../map_custom_field_form_data.jsx';
import TextFieldSettings from '../custom_text/custom_field_settings.jsx';
import CheckboxFieldSettings from '../custom_checkbox/custom_field_settings.jsx';
import DateFieldSettings from '../custom_date/custom_field_settings.jsx';
import RadioAndSelectFieldSettings from '../custom_radio/custom_field_settings.jsx';

export const customFieldTypes = [
  {
    value: 'text',
    label: MailPoet.I18n.t('customFieldTypeText'),
  },
  {
    value: 'textarea',
    label: MailPoet.I18n.t('customFieldTypeTextarea'),
  },
  {
    value: 'radio',
    label: MailPoet.I18n.t('customFieldTypeRadio'),
  },
  {
    value: 'checkbox',
    label: MailPoet.I18n.t('customFieldTypeCheckbox'),
  },
  {
    value: 'select',
    label: MailPoet.I18n.t('customFieldTypeSelect'),
  },
  {
    value: 'date',
    label: MailPoet.I18n.t('customFieldTypeDate'),
  },
];

const AddCustomField = ({ clientId }) => {
  const [fieldType, setFieldType] = useState('text');
  const [fieldName, setFieldName] = useState(null);
  const [fieldSettings, setFieldSettings] = useState(null);

  const canSubmit = () => fieldName && fieldSettings;

  const dateSettings = useSelect(
    (sel) => sel('mailpoet-form-editor').getDateSettingsData(),
    []
  );

  const defaultType = dateSettings.dateTypes[0].value;
  const defaultFormat = dateSettings.dateFormats[defaultType][0];

  const renderSettingsForType = () => {
    switch (fieldType) {
      case 'checkbox':
        return (
          <CheckboxFieldSettings
            mandatory={fieldSettings && fieldSettings.mandatory ? fieldSettings.mandatory : false}
            isChecked={fieldSettings && fieldSettings.isChecked ? fieldSettings.isChecked : false}
            checkboxLabel={fieldSettings && fieldSettings.checkboxLabel ? fieldSettings.checkboxLabel : ''}
            onChange={setFieldSettings}
          />
        );
      case 'date':
        return (
          <DateFieldSettings
            dateSettings={dateSettings}
            mandatory={fieldSettings && fieldSettings.mandatory ? fieldSettings.mandatory : false}
            dateFormat={fieldSettings && fieldSettings.dateFormat
              ? fieldSettings.dateFormat : defaultFormat}
            dateType={fieldSettings && fieldSettings.dateType
              ? fieldSettings.dateType : defaultType}
            defaultToday={fieldSettings && fieldSettings.defaultToday
              ? fieldSettings.defaultToday : false}
            onChange={setFieldSettings}
          />
        );
      case 'radio':
      case 'select':
        return (
          <RadioAndSelectFieldSettings
            mandatory={fieldSettings && fieldSettings.mandatory ? fieldSettings.mandatory : false}
            values={fieldSettings && fieldSettings.values ? fieldSettings.values : []}
            onChange={setFieldSettings}
          />
        );
      default:
        return (
          <TextFieldSettings
            mandatory={fieldSettings && fieldSettings.mandatory ? fieldSettings.mandatory : false}
            validate={fieldSettings && fieldSettings.validate ? fieldSettings.validate : ''}
            onChange={setFieldSettings}
          />
        );
    }
  };

  return (
    <Placeholder
      icon={<BlockIcon icon={icon} showColors />}
      label="New Custom Field"
    >
      <p id={clientId}>Create a new custom field for your subscribers.</p>
      <div className="mailpoet_custom_field_add_form">
        <hr />
        <SelectControl
          label="Select a field type"
          options={customFieldTypes}
          onChange={(value) => {
            setFieldSettings(null);
            setFieldType(value);
          }}
        />
        <TextControl
          label="Field name"
          onChange={setFieldName}
        />
        <hr />
        {renderSettingsForType()}
        <Button
          isLarge
          isDefault
          disabled={!canSubmit()}
          onClick={() => {
            // eslint-disable-next-line no-console
            console.log('Custom field data to store');
            const data = {
              name: fieldName,
              type: fieldType,
              params: mapCustomFieldFormData(fieldType, fieldSettings),
            };
            // eslint-disable-next-line no-console
            console.log(data);
          }}
        >
          {'Create'}
        </Button>
      </div>
    </Placeholder>
  );
};

AddCustomField.propTypes = {
  clientId: PropTypes.string.isRequired,
};

export default AddCustomField;
