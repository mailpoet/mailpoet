import React, { useState } from 'react';
import {
  Button,
  ToggleControl,
  TextControl,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const CustomFieldSettings = ({
  mandatory,
  dateType,
  dateFormat,
  defaultToday,
  isSaving,
  onSave,
}) => {
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localDefaultToday, setLocalLocalDefaultToday] = useState(defaultToday);
  const [localDateType, setLocalLocalDateType] = useState(dateType);
  const [localDateFormat, setLocalLocalDateFormat] = useState(dateFormat);

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
        disabled={isSaving}
        className="button-on-top"
      >
        {MailPoet.I18n.t('customFieldSaveCTA')}
      </Button>
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
      <TextControl
        label={MailPoet.I18n.t('customFieldDateType')}
        value={localDateType}
        onChange={setLocalLocalDateType}
      />
      <TextControl
        label={MailPoet.I18n.t('customFieldDateFormat')}
        value={localDateFormat}
        onChange={setLocalLocalDateFormat}
      />
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
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  isSaving: false,
  dateType: null,
  dateFormat: null,
  defaultToday: false,
};

export default CustomFieldSettings;
