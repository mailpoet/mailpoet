import { useEffect } from 'react';
import { isValid, parseISO } from 'date-fns';
import { useDispatch, useSelect } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';
import { Datepicker } from 'common/datepicker/datepicker';
import { Input } from 'common/form/input/input';

import { DateFormItem, FilterProps } from '../../types';
import { storeName } from '../../store';

export enum DateOperator {
  BEFORE = 'before',
  AFTER = 'after',
  ON = 'on',
  ON_OR_BEFORE = 'onOrBefore',
  ON_OR_AFTER = 'onOrAfter',
  NOT_ON = 'notOn',
  IN_THE_LAST = 'inTheLast',
  NOT_IN_THE_LAST = 'notInTheLast',
}

export type DateFilterProps = FilterProps & {
  defaultOperator: DateOperator;
};

const availableOperators = [
  DateOperator.BEFORE,
  DateOperator.AFTER,
  DateOperator.ON,
  DateOperator.ON_OR_AFTER,
  DateOperator.ON_OR_BEFORE,
  DateOperator.NOT_ON,
  DateOperator.IN_THE_LAST,
  DateOperator.NOT_IN_THE_LAST,
];

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
  return MailPoet.Date.format(value, { format: 'Y-m-d' });
};

const parseDate = (value: string): Date | undefined => {
  if (!value) return undefined;
  const date = parseISO(value);
  if (!isValid(date)) return undefined;
  return date;
};

function DateFields({
  filterIndex,
  defaultOperator,
}: DateFilterProps): JSX.Element {
  const segment: DateFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);

  useEffect(() => {
    if (!availableOperators.includes(segment.operator as DateOperator)) {
      void updateSegmentFilter({ operator: defaultOperator }, filterIndex);
    }
    if (
      (segment.operator === DateOperator.BEFORE ||
        segment.operator === DateOperator.AFTER ||
        segment.operator === DateOperator.ON ||
        segment.operator === DateOperator.ON_OR_AFTER ||
        segment.operator === DateOperator.ON_OR_BEFORE ||
        segment.operator === DateOperator.NOT_ON) &&
      (parseDate(segment.value) === undefined ||
        !/^\d+-\d+-\d+$/.test(segment.value))
    ) {
      void updateSegmentFilter(
        { value: convertDateToString(new Date()) },
        filterIndex,
      );
    }
    if (
      (segment.operator === DateOperator.IN_THE_LAST ||
        segment.operator === DateOperator.NOT_IN_THE_LAST) &&
      typeof segment.value === 'string' &&
      !/^\d*$/.exec(segment.value)
    ) {
      void updateSegmentFilter({ value: '' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex, defaultOperator]);

  return (
    <>
      <Select
        key="select"
        value={segment.operator}
        isMinWidth
        onChange={(e) => {
          void updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value={DateOperator.BEFORE}>{MailPoet.I18n.t('before')}</option>
        <option value={DateOperator.ON_OR_BEFORE}>
          {MailPoet.I18n.t('onOrBefore')}
        </option>
        <option value={DateOperator.ON}>{MailPoet.I18n.t('on')}</option>
        <option value={DateOperator.NOT_ON}>{MailPoet.I18n.t('notOn')}</option>
        <option value={DateOperator.ON_OR_AFTER}>
          {MailPoet.I18n.t('onOrAfter')}
        </option>
        <option value={DateOperator.AFTER}>{MailPoet.I18n.t('after')}</option>
        <option value={DateOperator.IN_THE_LAST}>
          {MailPoet.I18n.t('inTheLast')}
        </option>
        <option value={DateOperator.NOT_IN_THE_LAST}>
          {MailPoet.I18n.t('notInTheLast')}
        </option>
      </Select>
      {(segment.operator === DateOperator.BEFORE ||
        segment.operator === DateOperator.AFTER ||
        segment.operator === DateOperator.ON ||
        segment.operator === DateOperator.ON_OR_AFTER ||
        segment.operator === DateOperator.ON_OR_BEFORE ||
        segment.operator === DateOperator.NOT_ON) && (
        <Datepicker
          className="mailpoet-segments-datepicker-small"
          dateFormat="MMM d, yyyy"
          onChange={(value): void => {
            void updateSegmentFilter(
              { value: convertDateToString(value) },
              filterIndex,
            );
          }}
          selected={segment.value ? parseDate(segment.value) : undefined}
        />
      )}
      {(segment.operator === DateOperator.IN_THE_LAST ||
        segment.operator === DateOperator.NOT_IN_THE_LAST) && (
        <>
          <Input
            className="mailpoet-segments-input-small"
            key="input"
            type="number"
            value={segment.value || ''}
            onChange={(e) => {
              void updateSegmentFilterFromEvent('value', filterIndex, e);
            }}
            min="1"
            placeholder={MailPoet.I18n.t('daysPlaceholder')}
          />
          <span>{MailPoet.I18n.t('daysPlaceholder')}</span>
        </>
      )}
    </>
  );
}

export function validateDateField(formItems: DateFormItem): boolean {
  if (!formItems.operator || !formItems.value) {
    return false;
  }

  if (
    [
      DateOperator.BEFORE,
      DateOperator.AFTER,
      DateOperator.ON,
      DateOperator.NOT_ON,
      DateOperator.ON_OR_BEFORE,
      DateOperator.ON_OR_AFTER,
    ].includes(formItems.operator as DateOperator)
  ) {
    const re = /^\d+-\d+-\d+$/;
    return re.test(formItems.value);
  }

  if (
    [DateOperator.IN_THE_LAST, DateOperator.NOT_IN_THE_LAST].includes(
      formItems.operator as DateOperator,
    )
  ) {
    const re = /^\d+$/;
    return re.test(formItems.value) && Number(formItems.value) > 0;
  }

  return false;
}

function withDefaults(defaultOperator: DateOperator) {
  return function dateFieldWithDefaults(props: FilterProps): JSX.Element {
    return <DateFields {...props} defaultOperator={defaultOperator} />;
  };
}

export const DateFieldsDefaultBefore = withDefaults(DateOperator.BEFORE);
export const DateFieldsDefaultInTheLast = withDefaults(
  DateOperator.IN_THE_LAST,
);
