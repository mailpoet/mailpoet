import React from 'react';
import { find } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import MailPoet from 'mailpoet';
import ReactSelect from 'common/form/react_select/react_select';

import { Text, validateText } from './custom_fields/text';
import { RadioSelect, validateRadioSelect } from './custom_fields/select';
import { Checkbox, validateCheckbox } from './custom_fields/checkbox';
import { CustomFieldDate, validateDate } from './custom_fields/date';

import {
  WordpressRoleFormItem,
  SelectOption,
  WindowCustomFields,
} from '../types';

enum CustomFieldsTypes {
  DATE = 'date',
  TEXT = 'text',
  TEXTAREA = 'textarea',
  RADIO = 'radio',
  CHECKBOX = 'checkbox',
  SELECT = 'select',
}

const validationMap = {
  [CustomFieldsTypes.TEXT]: validateText,
  [CustomFieldsTypes.TEXTAREA]: validateText,
  [CustomFieldsTypes.RADIO]: validateRadioSelect,
  [CustomFieldsTypes.SELECT]: validateRadioSelect,
  [CustomFieldsTypes.CHECKBOX]: validateCheckbox,
  [CustomFieldsTypes.DATE]: validateDate,
};

export function validateMailPoetCustomField(formItems: WordpressRoleFormItem): boolean {
  const validator: (WordpressRoleFormItem) => boolean = validationMap[formItems.custom_field_type];
  if (!validator) return false;

  return validator(formItems);
}

const componentsMap = {
  [CustomFieldsTypes.TEXT]: Text,
  [CustomFieldsTypes.TEXTAREA]: Text,
  [CustomFieldsTypes.RADIO]: RadioSelect,
  [CustomFieldsTypes.SELECT]: RadioSelect,
  [CustomFieldsTypes.CHECKBOX]: Checkbox,
  [CustomFieldsTypes.DATE]: CustomFieldDate,
};

export const MailPoetCustomFields: React.FunctionComponent = () => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { updateSegment } = useDispatch('mailpoet-dynamic-segments-form');

  const customFieldsList: WindowCustomFields = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getCustomFieldsList(),
    []
  );
  const selectedCustomField = find(
    { id: Number(segment.custom_field_id) },
    customFieldsList
  );
  const options = customFieldsList.map((currentValue) => ({
    value: currentValue.id.toString(),
    label: currentValue.name,
  }));

  const TypeComponent = componentsMap[segment.custom_field_type];

  return (
    <>
      <ReactSelect
        isFullWidth
        placeholder={MailPoet.I18n.t('selectCustomFieldPlaceholder')}
        options={options}
        automationId="select-custom-field"
        value={
          find(
            (option) => {
              if (!segment.custom_field_id) return undefined;
              return segment.custom_field_id === option.value;
            },
            options
          )
        }
        onChange={(option: SelectOption): void => {
          const customField = find({ id: Number(option.value) }, customFieldsList);
          if (!customField) return;
          updateSegment({
            custom_field_id: option.value,
            custom_field_type: customField.type,
            operator: undefined,
            value: undefined,
          });
        }}
      />
      {
        TypeComponent && (
          <TypeComponent
            customField={selectedCustomField}
          />
        )
      }
    </>
  );
};
