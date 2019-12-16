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
  lines,
  customFieldId,
  updateAttributes,
}) => {
  const [localMandatory, setLocalMandatory] = useState(mandatory);
  const [localValidate, setLocalValidate] = useState(validate);
  const [localLines, setLocalLines] = useState(lines);
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
              required: localMandatory ? '1' : undefined,
              validate: localValidate,
              lines: localLines,
            },
          },
          onFinish: () => updateAttributes({
            mandatory: localMandatory,
            validate: localValidate,
            lines: localLines,
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

      <SelectControl
        label={`${MailPoet.I18n.t('customFieldNumberOfLines')}:`}
        value={localLines}
        options={[
          {
            label: MailPoet.I18n.t('customField1Line'),
            value: '1',
          },
          {
            label: MailPoet.I18n.t('customField2Lines'),
            value: '2',
          },
          {
            label: MailPoet.I18n.t('customField3Lines'),
            value: '3',
          },
          {
            label: MailPoet.I18n.t('customField4Lines'),
            value: '4',
          },
          {
            label: MailPoet.I18n.t('customField5Lines'),
            value: '5',
          },
        ]}
        onChange={setLocalLines}
      />
    </>
  );
};

CustomFieldSettings.propTypes = {
  mandatory: PropTypes.bool,
  validate: PropTypes.string,
  lines: PropTypes.string,
  customFieldId: PropTypes.number.isRequired,
  updateAttributes: PropTypes.func.isRequired,
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  validate: '',
  lines: '1',
};

export default CustomFieldSettings;
