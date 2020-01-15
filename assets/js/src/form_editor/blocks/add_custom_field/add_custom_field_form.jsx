import React, { useState } from 'react';
import PropTypes from 'prop-types';
import {
  Button,
  SelectControl,
  TextControl,
} from '@wordpress/components';

import MailPoet from 'mailpoet';

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

const AddCustomFieldForm = ({ dateSettings, onSubmit }) => {
  const [fieldType, setFieldType] = useState('text');
  const [fieldName, setFieldName] = useState(null);
  const [fieldSettings, setFieldSettings] = useState(null);

  const canSubmit = () => fieldName && fieldSettings;
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
            values={fieldSettings && fieldSettings.values ? fieldSettings.values : [{ name: '', id: Math.random().toString() }]}
            onChange={setFieldSettings}
            useDragAndDrop={false}
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
    <div className="mailpoet_custom_field_add_form">
      <hr />
      <SelectControl
        label={MailPoet.I18n.t('selectCustomFieldType')}
        options={customFieldTypes}
        onChange={(value) => {
          setFieldSettings(null);
          setFieldType(value);
        }}
      />
      <TextControl
        label={MailPoet.I18n.t('customFieldName')}
        onChange={setFieldName}
      />
      <hr />
      {renderSettingsForType()}
      <Button
        isLarge
        isDefault
        disabled={!canSubmit()}
        onClick={() => {
          const data = {
            name: fieldName,
            type: fieldType,
            params: mapCustomFieldFormData(fieldType, fieldSettings),
          };
          onSubmit(data);
        }}
      >
        {MailPoet.I18n.t('blockCreateButton')}
      </Button>
    </div>
  );
};

AddCustomFieldForm.propTypes = {
  dateSettings: PropTypes.shape({
    dateTypes: PropTypes.arrayOf(PropTypes.shape({
      label: PropTypes.string,
      value: PropTypes.string,
    })),
    dateFormats: PropTypes.objectOf(PropTypes.arrayOf(PropTypes.string)),
    months: PropTypes.arrayOf(PropTypes.string),
  }).isRequired,
  onSubmit: PropTypes.func.isRequired,
};

export default AddCustomFieldForm;
