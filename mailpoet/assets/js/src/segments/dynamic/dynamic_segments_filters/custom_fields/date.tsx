import { useEffect } from 'react';
import { assign, range } from 'lodash/fp';
import { format, getYear, isValid, parseISO } from 'date-fns';
import { useSelect, useDispatch } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';
import { Grid } from 'common/grid';
import { Datepicker } from 'common/datepicker/datepicker';

import { WordpressRoleFormItem, OnFilterChange } from '../../types';

interface ComponentProps {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
  filterIndex: number;
}

function DateMonth({ onChange, item, filterIndex }: ComponentProps) {
  useEffect(() => {
    if (item.value === undefined || item.value === '') {
      onChange(assign(item, { value: '2017-01-01 00:00:00' }), filterIndex);
    }
  }, [onChange, item, filterIndex]);

  return (
    <Select
      key="select"
      value={item.value}
      onChange={(e) => {
        onChange(assign(item, { value: e.target.value }), filterIndex);
      }}
    >
      <option value="2017-01-01 00:00:00">{MailPoet.I18n.t('january')}</option>
      <option value="2017-02-01 00:00:00">{MailPoet.I18n.t('february')}</option>
      <option value="2017-03-01 00:00:00">{MailPoet.I18n.t('march')}</option>
      <option value="2017-04-01 00:00:00">{MailPoet.I18n.t('april')}</option>
      <option value="2017-05-01 00:00:00">{MailPoet.I18n.t('may')}</option>
      <option value="2017-06-01 00:00:00">{MailPoet.I18n.t('june')}</option>
      <option value="2017-07-01 00:00:00">{MailPoet.I18n.t('july')}</option>
      <option value="2017-08-01 00:00:00">{MailPoet.I18n.t('august')}</option>
      <option value="2017-09-01 00:00:00">
        {MailPoet.I18n.t('september')}
      </option>
      <option value="2017-10-01 00:00:00">{MailPoet.I18n.t('october')}</option>
      <option value="2017-11-01 00:00:00">{MailPoet.I18n.t('november')}</option>
      <option value="2017-12-01 00:00:00">{MailPoet.I18n.t('december')}</option>
    </Select>
  );
}

function DateYear({ onChange, item, filterIndex }: ComponentProps) {
  const currentYear = getYear(new Date());
  useEffect(() => {
    if (item.value === undefined || item.value === '') {
      onChange(
        assign(item, {
          value: `${currentYear}-01-01 00:00:00`,
          operator: 'equals',
        }),
        filterIndex,
      );
    }
  }, [currentYear, onChange, item, filterIndex]);

  return (
    <Grid.CenteredRow>
      <Select
        key="select-operator"
        value={item.operator}
        onChange={(e) => {
          onChange(assign(item, { operator: e.target.value }), filterIndex);
        }}
      >
        <option value="equals">{MailPoet.I18n.t('equals')}</option>
        <option value="before">{MailPoet.I18n.t('before')}</option>
        <option value="after">{MailPoet.I18n.t('after')}</option>
      </Select>
      <Select
        key="select-year"
        value={item.value}
        onChange={(e) => {
          onChange(assign(item, { value: e.target.value }), filterIndex);
        }}
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
}

const convertDateToString = (
  value: Date | [Date, Date],
): string | undefined => {
  if (value === null) {
    return undefined;
  }
  if (Array.isArray(value)) {
    throw new Error(
      'convertDateToString can process only single date array given',
    );
  }
  return format(value, 'yyyy-MM-dd 00:00:00');
};

const parseDate = (value: string): Date | undefined => {
  const date = parseISO(value);
  if (!isValid(date)) return undefined;
  return date;
};

function DateFullDate({ onChange, item, filterIndex }: ComponentProps) {
  useEffect(() => {
    if (item.value === undefined || item.value === '') {
      onChange(
        assign(item, {
          value: `${format(new Date(), 'yyyy-MM-dd')} 00:00:00`,
          operator: 'equals',
        }),
        filterIndex,
      );
    }
  }, [onChange, item, filterIndex]);

  return (
    <Grid.CenteredRow>
      <Select
        key="select-operator"
        value={item.operator}
        onChange={(e) => {
          onChange(assign(item, { operator: e.target.value }), filterIndex);
        }}
      >
        <option value="equals">{MailPoet.I18n.t('equals')}</option>
        <option value="before">{MailPoet.I18n.t('before')}</option>
        <option value="after">{MailPoet.I18n.t('after')}</option>
      </Select>
      <Datepicker
        dateFormat="MMM d, yyyy"
        onChange={(value): void =>
          onChange(
            assign(item, { value: convertDateToString(value) }),
            filterIndex,
          )
        }
        selected={item.value ? parseDate(item.value) : undefined}
      />
    </Grid.CenteredRow>
  );
}

function DateMonthYear({ onChange, item, filterIndex }: ComponentProps) {
  useEffect(() => {
    if (item.value === undefined || item.value === '') {
      onChange(
        assign(item, {
          value: `${format(new Date(), 'yyyy-MM-dd')} 00:00:00`,
          operator: 'equals',
        }),
        filterIndex,
      );
    }
  }, [onChange, item, filterIndex]);

  return (
    <Grid.CenteredRow>
      <Select
        key="select-operator"
        value={item.operator}
        onChange={(e) => {
          onChange(assign(item, { operator: e.target.value }), filterIndex);
        }}
      >
        <option value="equals">{MailPoet.I18n.t('equals')}</option>
        <option value="before">{MailPoet.I18n.t('before')}</option>
        <option value="after">{MailPoet.I18n.t('after')}</option>
      </Select>
      <Datepicker
        onChange={(value): void =>
          onChange(
            assign(item, { value: convertDateToString(value) }),
            filterIndex,
          )
        }
        selected={item.value ? parseDate(item.value) : undefined}
        dateFormat="MM/yyyy"
        showMonthYearPicker
      />
    </Grid.CenteredRow>
  );
}

export function validateDate(item: WordpressRoleFormItem): boolean {
  if (
    item.date_type !== 'month' &&
    (typeof item.operator !== 'string' || item.operator.length < 1)
  ) {
    return false;
  }
  return typeof item.value === 'string' && item.value.length > 1;
}

interface Props {
  customField: {
    params: {
      date_type: string;
    };
  };
  filterIndex: number;
}

const componentsMap = {
  month: DateMonth,
  year: DateYear,
  year_month: DateMonthYear,
  year_month_day: DateFullDate,
};

export function CustomFieldDate({
  customField,
  filterIndex,
}: Props): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter } = useDispatch('mailpoet-dynamic-segments-form');

  useEffect(() => {
    if (segment.date_type !== customField.params.date_type) {
      void updateSegmentFilter(
        { date_type: customField.params.date_type, value: '' },
        filterIndex,
      );
    }
  }, [
    segment.date_type,
    updateSegmentFilter,
    customField.params.date_type,
    filterIndex,
  ]);

  const Component = componentsMap[customField.params.date_type];
  if (!Component) return null;
  return (
    <Component
      item={segment}
      onChange={updateSegmentFilter}
      filterIndex={filterIndex}
    />
  );
}
