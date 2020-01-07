import React, { useState } from 'react';
import {
  Button,
  ToggleControl,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const CustomFieldSettings = ({
  mandatory,
  isSaving,
  onSave,
  isChecked,
  checkboxLabel,
}) => {
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localIsChecked, setLocalIsChecked] = useState(isChecked);
  const [localCheckboxLabel, setLocalCheckboxLabel] = useState(checkboxLabel);

  return (
    <div className="custom-field-settings">
      <Button
        isPrimary
        isDefault
        onClick={() => onSave({
          mandatory: localMandatory,
          isChecked: localIsChecked,
          checkboxLabel: localCheckboxLabel,
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
      <div>
        <input
          type="checkbox"
          checked={localIsChecked}
          onChange={(event) => setLocalIsChecked(!!event.target.checked)}
        />
        <input
          value={localCheckboxLabel}
          onChange={(event) => setLocalCheckboxLabel(event.target.value)}
        />
      </div>
    </div>
  );
};

CustomFieldSettings.propTypes = {
  mandatory: PropTypes.bool,
  onSave: PropTypes.func.isRequired,
  isSaving: PropTypes.bool,
  isChecked: PropTypes.bool,
  checkboxLabel: PropTypes.string,
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  isSaving: false,
  isChecked: false,
  checkboxLabel: '',
};

export default CustomFieldSettings;
