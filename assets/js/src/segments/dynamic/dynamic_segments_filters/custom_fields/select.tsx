import React from 'react';
import {
  find,
} from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import MailPoet from 'mailpoet';
import ReactSelect from 'common/form/react_select/react_select';

import {
  WordpressRoleFormItem,
  SelectOption,
  WindowCustomFields,
} from '../../types';

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

export const RadioSelect: React.FunctionComponent = () => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { updateSegment } = useDispatch('mailpoet-dynamic-segments-form');

  const customFieldsList: WindowCustomFields = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getCustomFieldsList(),
    []
  );
  const customField = find({ id: Number(segment.custom_field_id) }, customFieldsList);
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
        dimension="small"
        isFullWidth
        placeholder={MailPoet.I18n.t('selectValue')}
        options={options}
        value={
          segment.value ? { value: segment.value, label: segment.value } : null
        }
        onChange={(option: SelectOption): void => {
          updateSegment({ value: option.value, operator: 'equals' });
        }}
        automationId="segment-wordpress-role"
      />
    </>
  );
};
