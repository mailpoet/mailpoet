import React, { useState } from 'react';
import {
  Button,
  ToggleControl,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import CustomFieldDelete from '../custom_field_delete.jsx';

const CustomFieldSettings = ({
  mandatory,
  isSaving,
  onSave,
  isChecked,
  checkboxLabel,
  isDeleting,
  displayCustomFieldDeleteConfirm,
  onCustomFieldDeleteClick,
  onCustomFieldDeleteCancel,
  onCustomFieldDeleteConfirm,
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
        disabled={
          isSaving
          || (
            localMandatory === mandatory
            && localIsChecked === isChecked
            && localCheckboxLabel === checkboxLabel
          )
        }
        className="button-on-top"
      >
        {MailPoet.I18n.t('customFieldSaveCTA')}
      </Button>
      <CustomFieldDelete
        isBusy={isSaving || isDeleting}
        displayConfirm={displayCustomFieldDeleteConfirm}
        onDeleteClick={onCustomFieldDeleteClick}
        onDeleteConfirm={onCustomFieldDeleteConfirm}
        onDeleteCancel={onCustomFieldDeleteCancel}
      />
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
  isDeleting: PropTypes.bool,
  displayCustomFieldDeleteConfirm: PropTypes.bool,
  onCustomFieldDeleteClick: PropTypes.func,
  onCustomFieldDeleteConfirm: PropTypes.func,
  onCustomFieldDeleteCancel: PropTypes.func,
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  isSaving: false,
  isChecked: false,
  checkboxLabel: '',
  isDeleting: false,
  displayCustomFieldDeleteConfirm: false,
  onCustomFieldDeleteClick: () => {},
  onCustomFieldDeleteConfirm: () => {},
  onCustomFieldDeleteCancel: () => {},
};

export default CustomFieldSettings;
