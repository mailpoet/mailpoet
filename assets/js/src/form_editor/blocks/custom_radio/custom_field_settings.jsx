import React, { useState } from 'react';
import {
  Button,
  ToggleControl,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import { isEmpty, isEqual, xorWith } from 'lodash';

import SettingsPreview from './settings_preview.jsx';

const CustomFieldSettings = ({
  mandatory,
  values,
  isSaving,
  onSave,
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

  return (
    <div className="custom-field-settings">
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
            && isEmpty(xorWith(values, localValues, isEqual))
          )
        }
        className="button-on-top"
      >
        {MailPoet.I18n.t('customFieldSaveCTA')}
      </Button>
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
  onSave: PropTypes.func.isRequired,
  isSaving: PropTypes.bool,
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  isSaving: false,
  values: [],
};

export default CustomFieldSettings;
