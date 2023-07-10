import { useDispatch, useSelect } from '@wordpress/data';
import { Input } from 'common';
import { MailPoet } from 'mailpoet';
import { DaysPeriodItem, FilterProps } from 'segments/dynamic/types';
import { storeName } from 'segments/dynamic/store';

function replaceElementsInDaysSentence(
  fn: (value) => JSX.Element,
): JSX.Element[] {
  return MailPoet.I18n.t('emailActionOpensDaysSentence')
    .split(/({days})/gim)
    .map(fn);
}

export function DaysPeriodField({ filterIndex }: FilterProps): JSX.Element {
  const segment: DaysPeriodItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const { updateSegmentFilterFromEvent } = useDispatch(storeName);

  return (
    <>
      {replaceElementsInDaysSentence((match) => {
        if (match === '{days}') {
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
        if (typeof match === 'string' && match.trim().length > 1) {
          return <div key={match}>{match}</div>;
        }
        return null;
      })}
    </>
  );
}

export function validateDaysPeriod(formItems: DaysPeriodItem): boolean {
  return !!formItems.days;
}
