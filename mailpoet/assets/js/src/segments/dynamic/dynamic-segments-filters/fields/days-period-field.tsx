import { useDispatch, useSelect } from '@wordpress/data';
import { Input } from 'common';
import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';
import { DaysPeriodItem, FilterProps, Timeframe } from 'segments/dynamic/types';
import { storeName } from 'segments/dynamic/store';
import { useEffect } from 'react';
import { isInEnum } from '../../../../utils';

function replaceElementsInDaysSentence(
  fn: (value) => JSX.Element,
): JSX.Element[] {
  return MailPoet.I18n.t('emailActionOpensDaysSentence')
    .split(/({days})|({timeframe})/gim)
    .map(fn);
}

export function DaysPeriodField({ filterIndex }: FilterProps): JSX.Element {
  const segment: DaysPeriodItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilterFromEvent, updateSegmentFilter } =
    useDispatch(storeName);

  useEffect(() => {
    if (!isInEnum(segment.timeframe, Timeframe)) {
      void updateSegmentFilter(
        { timeframe: Timeframe.IN_THE_LAST },
        filterIndex,
      );
    }
  }, [segment, updateSegmentFilter, filterIndex]);

  const isInTheLast = segment.timeframe === Timeframe.IN_THE_LAST;

  return (
    <>
      {replaceElementsInDaysSentence((match) => {
        if (isInTheLast && match === '{days}') {
          return (
            <Input
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
          );
        }
        if (match === '{timeframe}') {
          return (
            <Select
              key="timeframe-select"
              value={segment.timeframe}
              onChange={(e) => {
                void updateSegmentFilterFromEvent('timeframe', filterIndex, e);
              }}
            >
              <option value="inTheLast">{MailPoet.I18n.t('inTheLast')}</option>
              <option value="allTime">{MailPoet.I18n.t('overAllTime')}</option>
            </Select>
          );
        }
        if (
          isInTheLast &&
          typeof match === 'string' &&
          match.trim().length > 1
        ) {
          return <div key={match}>{match}</div>;
        }
        return null;
      })}
    </>
  );
}

export function validateDaysPeriod(formItems: DaysPeriodItem): boolean {
  if (formItems.timeframe === Timeframe.ALL_TIME) {
    return true;
  }
  const days = parseInt(formItems.days, 10);
  return days >= 1;
}
