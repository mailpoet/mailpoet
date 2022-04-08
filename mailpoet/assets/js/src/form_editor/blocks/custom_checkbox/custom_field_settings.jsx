import { useEffect, useMemo, useState } from 'react';
import {
  BaseControl,
  Button,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { isEmpty } from 'lodash';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import CustomFieldDelete from '../custom_field_delete.jsx';

function CustomFieldSettings({
  label,
  mandatory,
  isSaving,
  onSave,
  isChecked,
  checkboxLabel,
  isDeleting,
  onCustomFieldDelete,
  onChange,
}) {
  const [localLabel, setLocalLabel] = useState(label);
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localIsChecked, setLocalIsChecked] = useState(isChecked);
  const [localCheckboxLabel, setLocalCheckboxLabel] = useState(checkboxLabel);

  const hasUnsavedChanges =
    localMandatory !== mandatory ||
    localIsChecked !== isChecked ||
    localLabel !== label ||
    localCheckboxLabel !== checkboxLabel;

  const localData = useMemo(
    () => ({
      mandatory: localMandatory,
      isChecked: localIsChecked,
      label: localLabel,
      checkboxLabel: localCheckboxLabel,
      isValid: !isEmpty(localCheckboxLabel),
    }),
    [localLabel, localMandatory, localIsChecked, localCheckboxLabel],
  );

  useEffect(() => {
    onChange(localData, hasUnsavedChanges);
  }, [localData, onChange, hasUnsavedChanges]);

  return (
    <div className="custom-field-settings">
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
      <BaseControl>
        <input
          type="checkbox"
          checked={localIsChecked}
          onChange={(event) => setLocalIsChecked(!!event.target.checked)}
        />
        <input
          value={localCheckboxLabel}
          data-automation-id="settings_custom_checkbox_value"
          type="text"
          onChange={(event) => setLocalCheckboxLabel(event.target.value)}
        />
      </BaseControl>
      {onSave ? (
        <Button
          isPrimary
          onClick={() => onSave(localData)}
          isBusy={isSaving}
          disabled={isSaving || !hasUnsavedChanges}
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
    </div>
  );
}

CustomFieldSettings.propTypes = {
  label: PropTypes.string,
  mandatory: PropTypes.bool,
  onSave: PropTypes.func,
  isSaving: PropTypes.bool,
  isChecked: PropTypes.bool,
  checkboxLabel: PropTypes.string,
  isDeleting: PropTypes.bool,
  onCustomFieldDelete: PropTypes.func,
  onChange: PropTypes.func,
};

CustomFieldSettings.defaultProps = {
  label: '',
  mandatory: false,
  onSave: null,
  isSaving: false,
  isChecked: false,
  checkboxLabel: '',
  isDeleting: false,
  onCustomFieldDelete: null,
  onChange: () => {},
};

export default CustomFieldSettings;
