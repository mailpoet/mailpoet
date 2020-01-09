import React, { useState } from 'react';
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
}) => {
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localDefaultToday, setLocalLocalDefaultToday] = useState(defaultToday);
  const [localDateType, setLocalLocalDateType] = useState(dateType);
  const [localDateFormat, setLocalLocalDateFormat] = useState(dateFormat);

  const createDateFormatsSelect = () => {
    const dateFormats = dateSettings.dateFormats[localDateType];
    if (Array.isArray(dateFormats) && dateFormats.length === 1) {
      return null;
    }
    return (
      <SelectControl
        label={MailPoet.I18n.t('customFieldDateFormat')}
        value={localDateFormat}
        onChange={(value) => setLocalLocalDateFormat(value)}
        options={dateFormats.map((format) => ({
          value: format,
          label: format,
        }))}
      />
    );
  };

  return (
    <div className="custom-field-settings">
      <Button
        isPrimary
        isDefault
        onClick={() => onSave({
          mandatory: localMandatory,
          dateType: localDateType,
          dateFormat: localDateFormat,
          defaultToday: localDefaultToday,
        })}
        isBusy={isSaving}
        disabled={
          isSaving
          || (
            localMandatory === mandatory
            && localDefaultToday === defaultToday
            && localDateType === dateType
            && localDateFormat === dateFormat
          )
        }
        className="button-on-top"
      >
        {MailPoet.I18n.t('customFieldSaveCTA')}
      </Button>
      <CustomFieldDelete
        isBusy={isSaving || isDeleting}
        onDelete={onCustomFieldDelete}
      />
      <ToggleControl
        label={MailPoet.I18n.t('blockMandatory')}
        checked={localMandatory}
        onChange={setLocalMandatory}
      />
      <ToggleControl
        label={MailPoet.I18n.t('customFieldDefaultToday')}
        checked={localDefaultToday}
        onChange={setLocalLocalDefaultToday}
      />
      <SelectControl
        label={MailPoet.I18n.t('customFieldDateType')}
        value={localDateType}
        onChange={(value) => {
          setLocalLocalDateType(value);
          const dateFormats = dateSettings.dateFormats[value];
          if (dateFormats.length === 1) {
            setLocalLocalDateFormat(dateFormats[0]);
          }
        }}
        options={dateSettings.dateTypes}
      />
      {createDateFormatsSelect()}
    </div>
  );
};

CustomFieldSettings.propTypes = {
  mandatory: PropTypes.bool,
  dateType: PropTypes.string,
  dateFormat: PropTypes.string,
  defaultToday: PropTypes.bool,
  onSave: PropTypes.func.isRequired,
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
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  isSaving: false,
  dateType: null,
  dateFormat: null,
  defaultToday: false,
  isDeleting: false,
  onCustomFieldDelete: () => {},
};

export default CustomFieldSettings;
