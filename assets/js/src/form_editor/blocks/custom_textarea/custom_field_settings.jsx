import React, { useState } from 'react';
import {
  SelectControl,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import CustomTextSettings from '../custom_text/custom_field_settings.jsx';

const CustomFieldSettings = ({
  mandatory,
  validate,
  lines,
  isSaving,
  onSave,
}) => {
  const [localLines, setLocalLines] = useState(lines);

  return (
    <>
      <CustomTextSettings
        validate={validate}
        mandatory={mandatory}
        isSaving={isSaving}
        onSave={(customTextParams) => {
          onSave({
            ...customTextParams,
            lines: localLines,
          });
        }}
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
  onSave: PropTypes.func.isRequired,
  isSaving: PropTypes.bool,
};

CustomFieldSettings.defaultProps = {
  mandatory: false,
  validate: '',
  lines: '1',
  isSaving: false,
};

export default CustomFieldSettings;
