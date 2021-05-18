import React, { useEffect } from 'react';
import {
  __,
  assign,
  compose,
  get,
  set,
} from 'lodash/fp';

import MailPoet from 'mailpoet';
import Select from 'common/form/select/select';

import {
  WordpressRoleFormItem,
  OnFilterChange,
} from '../../types';

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

export function validateCheckbox(item: WordpressRoleFormItem): boolean {
  return ((item.value === '1') || (item.value === '0'));
}

export const Checkbox: React.FunctionComponent<Props> = ({ onChange, item }) => {
  useEffect(() => {
    if ((item.value !== '1') && (item.value !== '0')) {
      onChange(
        assign(item, { operator: 'equals', value: '1' })
      );
    }
  }, [onChange, item]);

  return (
    <>
      <div className="mailpoet-gap" />
      <Select
        key="select"
        value={item.value}
        onChange={compose([
          onChange,
          assign(item),
          set('value', __, {}),
          get('value'),
          get('target'),
        ])}
      >
        <option value="1">{MailPoet.I18n.t('checked')}</option>
        <option value="0">{MailPoet.I18n.t('unchecked')}</option>
      </Select>
    </>
  );
};
