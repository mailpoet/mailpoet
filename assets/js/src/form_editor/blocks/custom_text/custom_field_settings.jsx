import React, { useState } from 'react';
import {
  Button,
  SelectControl,
  ToggleControl,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

const CustomFieldSettings = ({
  mandatory,
  validate,
  customFieldId,
  updateAttributes,
}) => {
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localValidate, setLocalValidate] = useState(validate);
  const { saveCustomField } = useDispatch('mailpoet-form-editor');
  const isSaving = useSelect(
    (sel) => sel('mailpoet-form-editor').getIsCustomFieldSaving(),
    []
  );

  return (
    <>
      <Button
        isPrimary
        isDefault
        onClick={() => saveCustomField({
          customFieldId,
          data: {
            params: {
              required: '1',
              validate: localValidate,
            },
          },
          onFinish: () => updateAttributes({
            mandatory: localMandatory,
            validate: localValidate,
          }),
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
  customFieldId: PropTypes.number.isRequired,
  updateAttributes: PropTypes.func.isRequired,
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  validate: '',
};

export default CustomFieldSettings;
