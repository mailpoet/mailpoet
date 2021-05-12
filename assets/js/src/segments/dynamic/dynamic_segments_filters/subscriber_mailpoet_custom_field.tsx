import React from 'react';
import { assign, find } from 'lodash/fp';
import MailPoet from 'mailpoet';

import Select from 'common/form/react_select/react_select';
import { SegmentFormData } from '../segment_form_data';

import {
  WordpressRoleFormItem,
  OnFilterChange,
  SelectOption,
} from '../types';

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

export const MailPoetCustomFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
  const options = SegmentFormData.customFieldsList.map((currentValue) => ({
    value: currentValue.id.toString(),
    label: currentValue.name,
  }));

  return (
    <Select
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
        onChange(assign(item, { customFieldId: option.value, customFieldType: customField.type }));
      }}
      automationId="segment-wordpress-role"
    />
  );
};
