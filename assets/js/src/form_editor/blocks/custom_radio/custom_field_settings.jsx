import React, { useEffect, useMemo, useState } from 'react';
import {
  Button,
  ToggleControl,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import { isEqualWith } from 'lodash';

import SettingsPreview from './settings_preview.jsx';
import CustomFieldDelete from '../custom_field_delete.jsx';

const CustomFieldSettings = ({
  mandatory,
  values,
  isSaving,
  onSave,
  isDeleting,
  onCustomFieldDelete,
  onChange,
}) => {
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localValues, setLocalValues] = useState(JSON.parse(JSON.stringify(values)));

  const update = (value) => {
    setLocalValues(localValues.map((valueInSelection) => {
      if (value.id !== valueInSelection.id) {
        return valueInSelection;
      }
      return value;
    }));
  };

  const remove = (valueId) => {
    setLocalValues(
      localValues.filter((value) => valueId !== value.id)
    );
  };

  const localData = useMemo(() => ({
    mandatory: localMandatory,
    values: localValues,
  }), [localMandatory, localValues]);

  useEffect(() => {
    if (onChange) {
      onChange(localData);
    }
  }, [localData, onChange]);

  return (
    <div className="custom-field-settings">
      {onSave ? (
        <Button
          isPrimary
          isDefault
          onClick={() => onSave({
            mandatory: localMandatory,
            values: localValues,
          })}
          isBusy={isSaving}
          disabled={
            isSaving
            || (
              localMandatory === mandatory
              && isEqualWith(values, localValues)
            )
          }
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
      />
      <Button
        isLink
        onClick={() => setLocalValues([
          ...localValues,
          {
            id: `${Math.random() * 1000}-${Date.now()}`,
            name: `Option ${localValues.length + 1}`,
          },
        ])}
        className="button-on-top"
      >
        {MailPoet.I18n.t('customFieldAddItem')}
      </Button>
    </div>
  );
};

CustomFieldSettings.propTypes = {
  mandatory: PropTypes.bool,
  values: PropTypes.arrayOf(PropTypes.shape({
    name: PropTypes.string.isRequired,
    id: PropTypes.string.isRequired,
  })),
  onSave: PropTypes.func,
  isSaving: PropTypes.bool,
  isDeleting: PropTypes.bool,
  onCustomFieldDelete: PropTypes.func,
  onChange: PropTypes.func,
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  isSaving: false,
  values: [],
  isDeleting: false,
  onCustomFieldDelete: null,
  onSave: null,
  onChange: null,
};

export default CustomFieldSettings;
