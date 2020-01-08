import React, { useState } from 'react';
import {
  Button,
  SelectControl,
  ToggleControl,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import CustomFieldDelete from '../custom_field_delete.jsx';

const CustomFieldSettings = ({
  mandatory,
  validate,
  isSaving,
  onSave,
  displayCustomFieldDeleteConfirm,
  onCustomFieldDeleteClick,
  onCustomFieldDeleteCancel,
  onCustomFieldDeleteConfirm,
}) => {
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localValidate, setLocalValidate] = useState(validate);

  return (
    <>
      <Button
        isPrimary
        isDefault
        onClick={() => onSave({
          mandatory: localMandatory,
          validate: localValidate,
        })}
        isBusy={isSaving}
        disabled={isSaving || (localMandatory === mandatory && localValidate === validate)}
        className="button-on-top"
      >
        {MailPoet.I18n.t('customFieldSaveCTA')}
      </Button>
      <CustomFieldDelete
        isBusy={isSaving}
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
      <SelectControl
        label={`${MailPoet.I18n.t('customFieldValidateFor')}:`}
        options={[
          {
            label: MailPoet.I18n.t('customFieldValidateNothing'),
            value: '',
          },
          {
            label: MailPoet.I18n.t('customFieldValidateNumbersOnly'),
            value: 'alphanum',
          },
          {
            label: MailPoet.I18n.t('customFieldValidateAlphanumerical'),
            value: 'number',
          },
          {
            label: MailPoet.I18n.t('customFieldValidatePhoneNumber'),
            value: 'phone',
          },
        ]}
        value={localValidate}
        onChange={setLocalValidate}
      />
    </>
  );
};

CustomFieldSettings.propTypes = {
  mandatory: PropTypes.bool,
  validate: PropTypes.string,
  onSave: PropTypes.func.isRequired,
  isSaving: PropTypes.bool,
  displayCustomFieldDeleteConfirm: PropTypes.bool,
  onCustomFieldDeleteClick: PropTypes.func,
  onCustomFieldDeleteConfirm: PropTypes.func,
  onCustomFieldDeleteCancel: PropTypes.func,
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  isSaving: false,
  validate: '',
  displayCustomFieldDeleteConfirm: false,
  onCustomFieldDeleteClick: () => {},
  onCustomFieldDeleteConfirm: () => {},
  onCustomFieldDeleteCancel: () => {},
};

export default CustomFieldSettings;
