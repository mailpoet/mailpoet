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
import Input from 'common/form/input/input';
import { Grid } from 'common/grid';

import {
  WordpressRoleFormItem,
  OnFilterChange,
} from '../../types';

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

export function validateText(item: WordpressRoleFormItem): boolean {
  return (
    (typeof item.value === 'string')
    && (item.value.length > 0)
    && ((item.operator === 'equals') || (item.operator === 'contains'))
  );
}

export const Text: React.FunctionComponent<Props> = ({ onChange, item }) => {
  useEffect(() => {
    if (item.operator === undefined) {
      onChange(
        assign(item, { operator: 'equals', value: '' })
      );
    }
  }, [onChange, item]);

  return (
    <>
      <div className="mailpoet-gap" />
      <Grid.CenteredRow>
        <Select
          key="select"
          automationId="text-custom-field-operator"
          value={item.operator}
          onChange={compose([
            onChange,
            assign(item),
            set('operator', __, {}),
            get('value'),
            get('target'),
          ])}
        >
          <option value="equals">{MailPoet.I18n.t('equals')}</option>
          <option value="contains">{MailPoet.I18n.t('contains')}</option>
        </Select>
        <Input
          key="input"
          data-automation-id="text-custom-field-value"
          value={item.value || ''}
          onChange={compose([
            onChange,
            assign(item),
            set('value', __, {}),
            get('value'),
            get('target'),
          ])}
          placeholder={MailPoet.I18n.t('value')}
        />
      </Grid.CenteredRow>
    </>
  );
};
