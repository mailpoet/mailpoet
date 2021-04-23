import React, { useEffect } from 'react';
import { assign, compose } from 'lodash/fp';
import { isValid, parseISO } from 'date-fns';

import MailPoet from 'mailpoet';
import Select from 'common/form/select/select';
import Datepicker from 'common/datepicker/datepicker';
import { Grid } from 'common/grid';
import Input from 'common/form/input/input';

import {
  WordpressRoleFormItem,
  OnFilterChange,
} from '../types';

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

enum Operator {
  BEFORE = 'before',
  AFTER = 'after',
  IN_THE_LAST = 'inTheLast',
  NOT_IN_THE_LAST = 'notInTheLast',
}

const convertDateToString = (value: Date): string | undefined => {
  if (value === null) {
    return undefined;
  }
  return (MailPoet.Date.format(value, { format: 'Y-m-d' }));
};

const parseDate = (value: string): Date | undefined => {
  const date = parseISO(value);
  if (!isValid(date)) return undefined;
  return date;
};

export const SubscribedDateFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
  useEffect(() => {
    if (item.operator === undefined) {
      onChange(assign(item, { operator: Operator.BEFORE }));
    }
    if (
      (item.operator === Operator.BEFORE || item.operator === Operator.AFTER)
      && (parseDate(item.value) === undefined)
    ) {
      onChange(assign(item, { value: convertDateToString(new Date()) }));
    }
    if (
      (item.operator === Operator.IN_THE_LAST || item.operator === Operator.NOT_IN_THE_LAST)
      && ((typeof item.value !== 'string') || !new RegExp(/^\d+$/).exec(item.value))
    ) {
      onChange(assign(item, { value: '1' }));
    }
  }, [onChange, item]);

  return (
    <>
      <div className="mailpoet-gap" />
      <Grid.CenteredRow>
        <Select
          key="select"
          value={item.operator}
          onChange={(e): void => compose([
            onChange,
            assign(item),
          ])({ operator: e.target.value })}
        >
          <option value={Operator.BEFORE}>{MailPoet.I18n.t('before')}</option>
          <option value={Operator.AFTER}>{MailPoet.I18n.t('after')}</option>
          <option value={Operator.IN_THE_LAST}>{MailPoet.I18n.t('inTheLast')}</option>
          <option value={Operator.NOT_IN_THE_LAST}>{MailPoet.I18n.t('notInTheLast')}</option>
        </Select>
        {(item.operator === Operator.BEFORE || item.operator === Operator.AFTER) && (
          <Datepicker
            dateFormat="MMMM d, yyyy"
            onChange={(value): void => onChange(
              assign(item, { value: convertDateToString(value) })
            )}
            maxDate={new Date()}
            selected={item.value ? parseDate(item.value) : undefined}
          />
        )}
        {(item.operator === Operator.IN_THE_LAST || item.operator === Operator.NOT_IN_THE_LAST) && (
          <Input
            key="input"
            type="number"
            value={item.value}
            onChange={(e): void => onChange(assign(item, { value: e.target.value }))}
            min="1"
            placeholder={MailPoet.I18n.t('wooNumberOfOrdersDaysPlaceholder')}
          />
        )}
      </Grid.CenteredRow>
    </>
  );
};
