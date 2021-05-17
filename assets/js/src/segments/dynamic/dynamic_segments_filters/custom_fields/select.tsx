import React from 'react';
import {
  assign,
  find,
} from 'lodash/fp';

import MailPoet from 'mailpoet';
import ReactSelect from 'common/form/react_select/react_select';

import {
  WordpressRoleFormItem,
  OnFilterChange, SelectOption,
} from '../../types';
import { SegmentFormData } from '../../segment_form_data';

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

interface ParamsType {
  values?: {
    value: string;
  }[];
}

export function validateRadioSelect(item: WordpressRoleFormItem): boolean {
  return (
    (typeof item.value === 'string')
    && (item.value.length > 0)
  );
}

export const RadioSelect: React.FunctionComponent<Props> = ({ onChange, item }) => {
  const customField = find({ id: Number(item.custom_field_id) }, SegmentFormData.customFieldsList);
  if (!customField) return null;
  const params = (customField.params as ParamsType);
  if (!params || !Array.isArray(params.values)) return null;

  const options = params.values.map((currentValue) => ({
    value: currentValue.value,
    label: currentValue.value,
  }));

  return (
    <>
      <div className="mailpoet-gap" />
      <ReactSelect
        isFullWidth
        placeholder={MailPoet.I18n.t('selectValue')}
        options={options}
        value={
          item.value ? { value: item.value, label: item.value } : null
        }
        onChange={(option: SelectOption): void => {
          onChange(
            assign(item, { value: option.value, operator: 'equals' })
          );
        }}
        automationId="segment-wordpress-role"
      />
    </>
  );
};
