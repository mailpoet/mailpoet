import { useEffect, useMemo, useState } from 'react';
import { Button, TextControl, ToggleControl } from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import { reduce, isEmpty, isEqualWith } from 'lodash';

import SettingsPreview from './settings_preview.jsx';
import CustomFieldDelete from '../custom_field_delete.jsx';

function CustomFieldSettings({
  label,
  mandatory,
  values,
  isSaving,
  onSave,
  isDeleting,
  onCustomFieldDelete,
  onChange,
  useDragAndDrop,
}) {
  const [localLabel, setLocalLabel] = useState(label);
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localValues, setLocalValues] = useState(
    JSON.parse(JSON.stringify(values)),
  );

  const update = (value) => {
    setLocalValues(
      localValues.map((valueInSelection) => {
        if (value.id !== valueInSelection.id) {
          return valueInSelection;
        }
        return value;
      }),
    );
  };

  const remove = (valueId) => {
    setLocalValues(localValues.filter((value) => valueId !== value.id));
  };

  const localData = useMemo(
    () => ({
      label: localLabel,
      mandatory: localMandatory,
      values: localValues,
      isValid: reduce(
        localValues,
        (acc, value) => !isEmpty(value.name) && acc,
        true,
      ),
    }),
    [localLabel, localMandatory, localValues],
  );

  const hasUnsavedChanges =
    localMandatory !== mandatory ||
    !isEqualWith(values, localValues) ||
    localLabel !== label;

  useEffect(() => {
    if (onChange) {
      onChange(localData, hasUnsavedChanges);
    }
  }, [localData, onChange, hasUnsavedChanges]);

  return (
    <div
      className="custom-field-settings"
      data-automation-id="custom_field_settings"
    >
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
      <SettingsPreview
        remove={remove}
        update={update}
        values={localValues}
        onReorder={setLocalValues}
        useDragAndDrop={useDragAndDrop}
      />
      <Button
        isLink
        onClick={() =>
          setLocalValues([
            ...localValues,
            {
              id: `${Math.random() * 1000}-${Date.now()}`,
              name: `Option ${localValues.length + 1}`,
            },
          ])
        }
        className="button-on-top"
        data-automation-id="custom_field_values_add_item"
      >
        {MailPoet.I18n.t('customFieldAddItem')}
      </Button>
      <br />
      {onSave ? (
        <Button
          isPrimary
          onClick={() =>
            onSave({
              mandatory: localMandatory,
              values: localValues,
              label: localLabel,
            })
          }
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
  values: PropTypes.arrayOf(
    PropTypes.shape({
      name: PropTypes.string.isRequired,
      id: PropTypes.string.isRequired,
    }),
  ),
  onSave: PropTypes.func,
  isSaving: PropTypes.bool,
  isDeleting: PropTypes.bool,
  onCustomFieldDelete: PropTypes.func,
  onChange: PropTypes.func,
  useDragAndDrop: PropTypes.bool,
};

CustomFieldSettings.defaultProps = {
  label: '',
  mandatory: false,
  isSaving: false,
  values: [],
  isDeleting: false,
  onCustomFieldDelete: null,
  onSave: null,
  onChange: null,
  useDragAndDrop: true,
};

export default CustomFieldSettings;
