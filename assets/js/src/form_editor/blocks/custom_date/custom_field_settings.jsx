import React, { useEffect, useMemo, useState } from 'react';
import {
  Button,
  ToggleControl,
  SelectControl,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import CustomFieldDelete from '../custom_field_delete.jsx';

const CustomFieldSettings = ({
  mandatory,
  dateType,
  dateFormat,
  defaultToday,
  dateSettings,
  isSaving,
  onSave,
  isDeleting,
  onCustomFieldDelete,
  onChange,
}) => {
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localDefaultToday, setLocalDefaultToday] = useState(defaultToday);
  const [localDateType, setLocalDateType] = useState(dateType);
  const [localDateFormat, setLocalDateFormat] = useState(dateFormat);

  const localData = useMemo(() => ({
    mandatory: localMandatory,
    dateType: localDateType,
    dateFormat: localDateFormat,
    defaultToday: localDefaultToday,
  }), [localMandatory, localDateType, localDateFormat, localDefaultToday]);

  const hasUnsavedChanges = localMandatory !== mandatory
    || localDefaultToday !== defaultToday
    || localDateType !== dateType
    || localDateFormat !== dateFormat;

  useEffect(() => {
    if (onChange) {
      onChange(localData, hasUnsavedChanges);
    }
  }, [localData, onChange, hasUnsavedChanges]);

  const createDateFormatsSelect = () => {
    const dateFormats = dateSettings.dateFormats[localDateType];
    if (Array.isArray(dateFormats) && dateFormats.length === 1) {
      return null;
    }
    return (
      <SelectControl
        label={MailPoet.I18n.t('customFieldDateFormat')}
        data-automation-id="settings_custom_date_format"
        value={localDateFormat}
        onChange={(value) => setLocalDateFormat(value)}
        options={dateFormats.map((format) => ({
          value: format,
          label: format,
        }))}
      />
    );
  };

  return (
    <div className="custom-field-settings">
      <ToggleControl
        label={MailPoet.I18n.t('blockMandatory')}
        checked={localMandatory}
        onChange={setLocalMandatory}
      />
      <ToggleControl
        label={MailPoet.I18n.t('customFieldDefaultToday')}
        checked={localDefaultToday}
        onChange={setLocalDefaultToday}
      />
      <SelectControl
        label={MailPoet.I18n.t('customFieldDateType')}
        data-automation-id="settings_custom_date_type"
        value={localDateType}
        onChange={(value) => {
          setLocalDateType(value);
          const dateFormats = dateSettings.dateFormats[value];
          setLocalDateFormat(dateFormats[0]);
        }}
        options={dateSettings.dateTypes}
      />
      {createDateFormatsSelect()}
      {onSave ? (
        <Button
          isPrimary
          onClick={() => onSave({
            mandatory: localMandatory,
            dateType: localDateType,
            dateFormat: localDateFormat,
            defaultToday: localDefaultToday,
          })}
          isBusy={isSaving}
          disabled={isSaving || !hasUnsavedChanges}
          className="button-on-top"
        >
          {MailPoet.I18n.t('customFieldSaveCTA')}
        </Button>
      ) : null}
      {onCustomFieldDelete ? (
        <CustomFieldDelete
          isBusy={isSaving || isDeleting}
          onDelete={onCustomFieldDelete}
        />
      ) : null}
    </div>
  );
};

CustomFieldSettings.propTypes = {
  mandatory: PropTypes.bool,
  dateType: PropTypes.string,
  dateFormat: PropTypes.string,
  defaultToday: PropTypes.bool,
  onSave: PropTypes.func,
  isSaving: PropTypes.bool,
  dateSettings: PropTypes.shape({
    dateTypes: PropTypes.arrayOf(PropTypes.shape({
      label: PropTypes.string,
      value: PropTypes.string,
    })),
    dateFormats: PropTypes.objectOf(PropTypes.arrayOf(PropTypes.string)),
    months: PropTypes.arrayOf(PropTypes.string),
  }).isRequired,
  isDeleting: PropTypes.bool,
  onCustomFieldDelete: PropTypes.func,
  onChange: PropTypes.func,
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  isSaving: false,
  dateType: null,
  dateFormat: null,
  defaultToday: false,
  isDeleting: false,
  onCustomFieldDelete: null,
  onSave: null,
  onChange: null,
};

export default CustomFieldSettings;
