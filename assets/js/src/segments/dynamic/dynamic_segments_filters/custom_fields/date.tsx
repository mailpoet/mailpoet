import React, { useEffect } from 'react';
import {
  __,
  assign,
  compose,
  get,
  set,
  range,
} from 'lodash/fp';
import {
  format,
  getYear,
  isValid,
  parseISO,
} from 'date-fns';

import MailPoet from 'mailpoet';
import Select from 'common/form/select/select';
import { Grid } from 'common/grid';
import Datepicker from 'common/datepicker/datepicker';

import {
  WordpressRoleFormItem,
  OnFilterChange,
} from '../../types';

interface ComponentProps {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

const DateMonth = ({ onChange, item }: ComponentProps) => {
  useEffect(() => {
    if ((item.value === undefined) || item.value === '') {
      onChange(
        assign(item, { value: '2017-01-01 00:00:00' })
      );
    }
  }, [onChange, item]);

  return (
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
      <option value="2017-01-01 00:00:00">{MailPoet.I18n.t('january')}</option>
      <option value="2017-02-01 00:00:00">{MailPoet.I18n.t('february')}</option>
      <option value="2017-03-01 00:00:00">{MailPoet.I18n.t('march')}</option>
      <option value="2017-04-01 00:00:00">{MailPoet.I18n.t('april')}</option>
      <option value="2017-05-01 00:00:00">{MailPoet.I18n.t('may')}</option>
      <option value="2017-06-01 00:00:00">{MailPoet.I18n.t('june')}</option>
      <option value="2017-07-01 00:00:00">{MailPoet.I18n.t('july')}</option>
      <option value="2017-08-01 00:00:00">{MailPoet.I18n.t('august')}</option>
      <option value="2017-09-01 00:00:00">{MailPoet.I18n.t('september')}</option>
      <option value="2017-10-01 00:00:00">{MailPoet.I18n.t('october')}</option>
      <option value="2017-11-01 00:00:00">{MailPoet.I18n.t('november')}</option>
      <option value="2017-12-01 00:00:00">{MailPoet.I18n.t('december')}</option>
    </Select>
  );
};

const DateYear = ({ onChange, item }: ComponentProps) => {
  const currentYear = getYear(new Date());
  useEffect(() => {
    if ((item.value === undefined) || item.value === '') {
      onChange(
        assign(item, {
          value: `${currentYear}-01-01 00:00:00`,
          operator: 'equals',
        })
      );
    }
  }, [currentYear, onChange, item]);

  return (
    <Grid.CenteredRow>
      <Select
        key="select-operator"
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
        <option value="before">{MailPoet.I18n.t('before')}</option>
        <option value="after">{MailPoet.I18n.t('after')}</option>
      </Select>
      <Select
        key="select-year"
        value={item.value}
        onChange={compose([
          onChange,
          assign(item),
          set('value', __, {}),
          get('value'),
          get('target'),
        ])}
      >
        {range(0, 100).map((sub) => (
          <option
            value={`${currentYear - sub}-01-01 00:00:00`}
            key={currentYear - sub}
          >
            {currentYear - sub}
          </option>
        ))}
      </Select>
    </Grid.CenteredRow>
  );
};

const convertDateToString = (value: Date): string | undefined => {
  if (value === null) {
    return undefined;
  }
  return format(value, 'yyyy-MM-dd 00:00:00');
};

const parseDate = (value: string): Date | undefined => {
  const date = parseISO(value);
  if (!isValid(date)) return undefined;
  return date;
};

const DateFullDate = ({ onChange, item }: ComponentProps) => {
  useEffect(() => {
    if ((item.value === undefined) || item.value === '') {
      onChange(
        assign(item, {
          value: `${format(new Date(), 'yyyy-MM-dd')} 00:00:00`,
          operator: 'equals',
        })
      );
    }
  }, [onChange, item]);

  return (
    <Grid.CenteredRow>
      <Select
        key="select-operator"
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
        <option value="before">{MailPoet.I18n.t('before')}</option>
        <option value="after">{MailPoet.I18n.t('after')}</option>
      </Select>
      <Datepicker
        dateFormat="MMMM d, yyyy"
        onChange={(value): void => onChange(
          assign(item, { value: convertDateToString(value) })
        )}
        selected={item.value ? parseDate(item.value) : undefined}
      />
    </Grid.CenteredRow>
  );
};

export function validateDate(item: WordpressRoleFormItem): boolean {
  if ((item.date_type !== 'month')
    && (
      (typeof item.operator !== 'string')
      || (item.operator.length < 1)
    )
  ) {
    return false;
  }
  return (typeof item.value === 'string')
    && (item.value.length > 1);
}

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
  customField: {
    params: {
      date_type: string;
    }
  };
}

const componentsMap = {
  month: DateMonth,
  year: DateYear,
  year_month: DateFullDate,
  year_month_day: DateFullDate,
};

export const CustomFieldDate: React.FunctionComponent<Props> = (
  { onChange, item, customField }
) => {
  useEffect(() => {
    if (item.date_type === customField.params.date_type) {
      onChange(
        assign(item, { date_type: customField.params.date_type, value: '' })
      );
    }
  }, [onChange, item, customField.params.date_type]);

  const Component = componentsMap[customField.params.date_type];
  if (!Component) return null;
  return (
    <>
      <div className="mailpoet-gap" />
      <Component
        item={item}
        onChange={onChange}
      />
    </>
  );
};
