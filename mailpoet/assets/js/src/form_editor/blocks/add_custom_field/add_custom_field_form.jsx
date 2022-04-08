import { useState } from 'react';
import PropTypes from 'prop-types';
import { Button, SelectControl, TextControl } from '@wordpress/components';

import MailPoet from 'mailpoet';
import { isEmpty } from 'lodash';

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

function AddCustomFieldForm({ dateSettings, onSubmit }) {
  const [fieldType, setFieldType] = useState('text');
  const [fieldName, setFieldName] = useState(null);
  const [fieldSettings, setFieldSettings] = useState({});

  const canSubmit =
    fieldName && !isEmpty(fieldSettings) && fieldSettings.isValid !== false;
  const defaultType = dateSettings.dateTypes[0].value;
  const defaultFormat = dateSettings.dateFormats[defaultType][0];

  const renderSettingsForType = () => {
    switch (fieldType) {
      case 'checkbox':
        return (
          <CheckboxFieldSettings
            mandatory={
              fieldSettings.mandatory ? fieldSettings.mandatory : false
            }
            isChecked={
              fieldSettings.isChecked ? fieldSettings.isChecked : false
            }
            checkboxLabel={
              fieldSettings.checkboxLabel ? fieldSettings.checkboxLabel : ''
            }
            onChange={setFieldSettings}
          />
        );
      case 'date':
        return (
          <DateFieldSettings
            dateSettings={dateSettings}
            mandatory={
              fieldSettings.mandatory ? fieldSettings.mandatory : false
            }
            dateFormat={
              fieldSettings.dateFormat
                ? fieldSettings.dateFormat
                : defaultFormat
            }
            dateType={
              fieldSettings.dateType ? fieldSettings.dateType : defaultType
            }
            defaultToday={
              fieldSettings.defaultToday ? fieldSettings.defaultToday : false
            }
            onChange={setFieldSettings}
          />
        );
      case 'radio':
      case 'select':
        return (
          <RadioAndSelectFieldSettings
            mandatory={
              fieldSettings.mandatory ? fieldSettings.mandatory : false
            }
            values={
              fieldSettings.values
                ? fieldSettings.values
                : [{ name: '', id: Math.random().toString() }]
            }
            onChange={setFieldSettings}
          />
        );
      default:
        return (
          <TextFieldSettings
            mandatory={
              fieldSettings.mandatory ? fieldSettings.mandatory : false
            }
            validate={fieldSettings.validate ? fieldSettings.validate : ''}
            fieldType={fieldType}
            onChange={setFieldSettings}
          />
        );
    }
  };

  return (
    <div
      className="mailpoet_custom_field_add_form"
      data-automation-id="create_custom_field_form"
    >
      <hr />
      <SelectControl
        label={MailPoet.I18n.t('selectCustomFieldType')}
        options={customFieldTypes}
        data-automation-id="create_custom_field_type_select"
        onChange={(value) => {
          setFieldSettings({});
          setFieldType(value);
        }}
      />
      <TextControl
        label={MailPoet.I18n.t('customFieldName')}
        onChange={setFieldName}
        data-automation-id="create_custom_field_name_input"
      />
      <hr />
      {renderSettingsForType()}
      <Button
        isSecondary
        disabled={!canSubmit}
        data-automation-id="create_custom_field_submit"
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
}

AddCustomFieldForm.propTypes = {
  dateSettings: PropTypes.shape({
    dateTypes: PropTypes.arrayOf(
      PropTypes.shape({
        label: PropTypes.string,
        value: PropTypes.string,
      }),
    ),
    dateFormats: PropTypes.objectOf(PropTypes.arrayOf(PropTypes.string)),
    months: PropTypes.arrayOf(PropTypes.string),
  }).isRequired,
  onSubmit: PropTypes.func.isRequired,
};

export default AddCustomFieldForm;
