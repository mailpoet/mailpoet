import React from 'react';
import { assign, find } from 'lodash/fp';

import MailPoet from 'mailpoet';
import ReactSelect from 'common/form/react_select/react_select';

import { SegmentFormData } from '../segment_form_data';
import { Text } from './custom_fields/text';
import { RadioSelect } from './custom_fields/select';

import {
  WordpressRoleFormItem,
  OnFilterChange,
  SelectOption,
} from '../types';

enum CustomFieldsTypes {
  DATE = 'date',
  TEXT = 'text',
  TEXTAREA = 'textarea',
  RADIO = 'radio',
  CHECKBOX = 'checkbox',
  SELECT = 'select',
}

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

const componentsMap = {
  [CustomFieldsTypes.TEXT]: Text,
  [CustomFieldsTypes.TEXTAREA]: Text,
  [CustomFieldsTypes.RADIO]: RadioSelect,
  [CustomFieldsTypes.SELECT]: RadioSelect,
};

export const MailPoetCustomFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
  const options = SegmentFormData.customFieldsList.map((currentValue) => ({
    value: currentValue.id.toString(),
    label: currentValue.name,
  }));

  const TypeComponent = componentsMap[item.customFieldType];

  return (
    <>
      <ReactSelect
        isFullWidth
        placeholder={MailPoet.I18n.t('selectUserRolePlaceholder')}
        options={options}
        value={
          find(
            (option) => {
              if (!item.customFieldId) return undefined;
              return item.customFieldId === option.value;
            },
            options
          )
        }
        onChange={(option: SelectOption): void => {
          const customField = find({ id: Number(option.value) }, SegmentFormData.customFieldsList);
          if (!customField) return;
          onChange(
            assign(item, {
              customFieldId: option.value,
              customFieldType: customField.type,
              operator: undefined,
              value: undefined,
            })
          );
        }}
        automationId="segment-wordpress-role"
      />
      {
        TypeComponent && (
          <TypeComponent
            item={item}
            onChange={onChange}
          />
        )
      }
    </>
  );
};
