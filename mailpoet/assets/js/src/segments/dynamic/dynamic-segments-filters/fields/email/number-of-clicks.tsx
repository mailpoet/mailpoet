import { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { Grid } from 'common/grid';
import { Select } from 'common/form/select/select';
import { Input } from 'common/form/input/input';
import { MailPoet } from 'mailpoet';

import { EmailFormItem, FilterProps } from '../../../types';
import { storeName } from '../../../store';
import { DaysPeriodField } from '../days-period-field';

function replaceEmailActionNumberOfClicks(
  fn: (value) => JSX.Element,
): JSX.Element[] {
  return __('{condition} {clicks} clicks', 'mailpoet')
    .split(/({condition})|({clicks})|(\b[a-zA-Z]+\b)/gim)
    .map(fn);
}

export function NumberOfClicksFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: EmailFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);
  useEffect(() => {
    if (!['more', 'less', 'equals', 'not_equals'].includes(segment.operator)) {
      void updateSegmentFilter({ operator: 'more' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      <Grid.CenteredRow>
        {replaceEmailActionNumberOfClicks((match) => {
          if (match === '{condition}') {
            return (
              <Select
                key="select"
                value={segment.operator}
                onChange={(e) => {
                  void updateSegmentFilterFromEvent('operator', filterIndex, e);
                }}
              >
                <option value="more">{MailPoet.I18n.t('moreThan')}</option>
                <option value="less">{MailPoet.I18n.t('lessThan')}</option>
                <option value="equals">{MailPoet.I18n.t('equals')}</option>
                <option value="not_equals">
                  {MailPoet.I18n.t('notEquals')}
                </option>
              </Select>
            );
          }
          if (match === '{clicks}') {
            return (
              <Input
                key="input"
                type="number"
                value={segment.clicks || ''}
                data-automation-id="segment-number-of-clicks"
                onChange={(e) => {
                  void updateSegmentFilterFromEvent('clicks', filterIndex, e);
                }}
                min="0"
                placeholder={__('clicks', 'mailpoet')}
              />
            );
          }
          if (typeof match === 'string' && match.trim().length > 1) {
            return <div key="clicks">{match}</div>;
          }
          return null;
        })}
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <DaysPeriodField filterIndex={filterIndex} />
      </Grid.CenteredRow>
    </>
  );
}
