import { useDispatch, useSelect } from '@wordpress/data';
import { Input } from 'common';
import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';
import { Datepicker } from 'common/datepicker/datepicker';
import { DaysPeriodItem, FilterProps, Timeframe } from 'segments/dynamic/types';
import { storeName } from 'segments/dynamic/store';
import { useEffect } from 'react';
import { isInEnum } from '../../../../utils';
import { convertDateToString, parseDate } from './date-helpers';

type Props = FilterProps & {
  defaultTimeframe?: Timeframe;
};

export function DaysPeriodField({
  filterIndex,
  defaultTimeframe = Timeframe.IN_THE_LAST,
}: Props): JSX.Element {
  const segment: DaysPeriodItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilterFromEvent, updateSegmentFilter } =
    useDispatch(storeName);

  useEffect(() => {
    if (!isInEnum(segment.timeframe, Timeframe)) {
      void updateSegmentFilter({ timeframe: defaultTimeframe }, filterIndex);
    }
    if (
      segment.timeframe === Timeframe.IN_THE_LAST &&
      typeof segment.days === 'string' &&
      !/^\d*$/.exec(segment.days)
    ) {
      void updateSegmentFilter({ days: '' }, filterIndex);
    }
    if (
      [Timeframe.BEFORE, Timeframe.AFTER, Timeframe.ON, Timeframe.BETWEEN].some(
        (timeframe) => timeframe === segment.timeframe,
      ) &&
      (parseDate(segment.value || '') === undefined ||
        !/^\d+-\d+-\d+$/.test(segment.value || ''))
    ) {
      void updateSegmentFilter(
        { value: convertDateToString(new Date()) },
        filterIndex,
      );
    }
    if (
      segment.timeframe === Timeframe.BETWEEN &&
      (parseDate(segment.value2 || '') === undefined ||
        !/^\d+-\d+-\d+$/.test(segment.value2 || ''))
    ) {
      void updateSegmentFilter(
        { value2: convertDateToString(new Date()) },
        filterIndex,
      );
    }
  }, [segment, updateSegmentFilter, filterIndex, defaultTimeframe]);

  const isInTheLast = segment.timeframe === Timeframe.IN_THE_LAST;
  const isSingleDate =
    segment.timeframe === Timeframe.BEFORE ||
    segment.timeframe === Timeframe.AFTER ||
    segment.timeframe === Timeframe.ON;

  return (
    <>
      <Select
        key="timeframe-select"
        value={segment.timeframe}
        onChange={(e) => {
          void updateSegmentFilterFromEvent('timeframe', filterIndex, e);
        }}
      >
        <option value={Timeframe.ALL_TIME}>
          {MailPoet.I18n.t('overAllTime')}
        </option>
        <option value={Timeframe.IN_THE_LAST}>
          {MailPoet.I18n.t('inTheLast')}
        </option>
        <option value={Timeframe.BEFORE}>{MailPoet.I18n.t('before')}</option>
        <option value={Timeframe.AFTER}>{MailPoet.I18n.t('after')}</option>
        <option value={Timeframe.ON}>{MailPoet.I18n.t('on')}</option>
        <option value={Timeframe.BETWEEN}>{MailPoet.I18n.t('between')}</option>
      </Select>
      {isInTheLast && (
        <div className="mailpoet-segments-date-period-controls">
          <Input
            className="mailpoet-segments-input-small"
            key="input"
            type="number"
            value={segment.days || ''}
            data-automation-id="segment-number-of-days"
            onChange={(e) => {
              void updateSegmentFilterFromEvent('days', filterIndex, e);
            }}
            min={1}
            step={1}
            placeholder={MailPoet.I18n.t('daysPlaceholder')}
          />
          <span>{MailPoet.I18n.t('daysPlaceholder')}</span>
        </div>
      )}
      {isSingleDate && (
        <div className="mailpoet-segments-date-period-controls">
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
        </div>
      )}
      {segment.timeframe === Timeframe.BETWEEN && (
        <div className="mailpoet-segments-date-period-controls">
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
          <Datepicker
            className="mailpoet-segments-datepicker-small"
            dateFormat="MMM d, yyyy"
            onChange={(value): void => {
              void updateSegmentFilter(
                { value2: convertDateToString(value) },
                filterIndex,
              );
            }}
            selected={segment.value2 ? parseDate(segment.value2) : undefined}
          />
        </div>
      )}
    </>
  );
}

export function validateDaysPeriod(formItems: DaysPeriodItem): boolean {
  if (formItems.timeframe === Timeframe.ALL_TIME) {
    return true;
  }
  if (
    [Timeframe.BEFORE, Timeframe.AFTER, Timeframe.ON].some(
      (timeframe) => timeframe === formItems.timeframe,
    )
  ) {
    return /^\d+-\d+-\d+$/.test(formItems.value || '');
  }
  if (formItems.timeframe === Timeframe.BETWEEN) {
    return (
      /^\d+-\d+-\d+$/.test(formItems.value || '') &&
      /^\d+-\d+-\d+$/.test(formItems.value2 || '')
    );
  }
  const days = parseInt(formItems.days, 10);
  return days >= 1;
}
