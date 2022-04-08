import { useState, useEffect, useMemo } from 'react';
import {
  Button,
  SelectControl,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import CustomFieldDelete from '../custom_field_delete.jsx';

function CustomFieldSettings({
  label,
  mandatory,
  validate,
  isSaving,
  onSave,
  isDeleting,
  onCustomFieldDelete,
  onChange,
  fieldType,
}) {
  const [localLabel, setLocalLabel] = useState(label);
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localValidate, setLocalValidate] = useState(validate);

  const localData = useMemo(
    () => ({
      label: localLabel,
      mandatory: localMandatory,
      validate: localValidate,
    }),
    [localLabel, localMandatory, localValidate],
  );

  const hasUnsavedChanges =
    localMandatory !== mandatory ||
    localValidate !== validate ||
    localLabel !== label;

  useEffect(() => {
    if (onChange) {
      onChange(localData, hasUnsavedChanges);
    }
  }, [localData, onChange, hasUnsavedChanges, fieldType]);

  return (
    <>
      <TextControl
        label={MailPoet.I18n.t('label')}
        value={localLabel}
        data-automation-id="settings_custom_text_label_input"
        onChange={setLocalLabel}
      />
      <ToggleControl
        label={MailPoet.I18n.t('blockMandatory')}
        checked={localMandatory}
        onChange={setLocalMandatory}
      />
      <SelectControl
        label={`${MailPoet.I18n.t('customFieldValidateFor')}:`}
        data-automation-id="settings_custom_text_input_validation_type"
        options={[
          {
            label: MailPoet.I18n.t('customFieldValidateNothing'),
            value: '',
          },
          {
            label: MailPoet.I18n.t('customFieldValidateNumbersOnly'),
            value: 'number',
          },
          {
            label: MailPoet.I18n.t('customFieldValidateAlphanumerical'),
            value: 'alphanum',
          },
          {
            label: MailPoet.I18n.t('customFieldValidatePhoneNumber'),
            value: 'phone',
          },
        ]}
        value={localValidate}
        onChange={setLocalValidate}
      />
      {onSave ? (
        <Button
          isPrimary
          onClick={() => onSave(localData)}
          isBusy={isSaving}
          disabled={isSaving || isDeleting || !hasUnsavedChanges}
          className="button-on-top"
          data-automation-id="custom_field_save"
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
    </>
  );
}

CustomFieldSettings.propTypes = {
  label: PropTypes.string,
  mandatory: PropTypes.bool,
  validate: PropTypes.string,
  onSave: PropTypes.func,
  isSaving: PropTypes.bool,
  isDeleting: PropTypes.bool,
  onCustomFieldDelete: PropTypes.func,
  onChange: PropTypes.func,
  fieldType: PropTypes.string,
};

CustomFieldSettings.defaultProps = {
  label: '',
  mandatory: false,
  fieldType: '',
  isSaving: false,
  validate: '',
  isDeleting: false,
  onCustomFieldDelete: null,
  onSave: null,
  onChange: null,
};

export default CustomFieldSettings;
