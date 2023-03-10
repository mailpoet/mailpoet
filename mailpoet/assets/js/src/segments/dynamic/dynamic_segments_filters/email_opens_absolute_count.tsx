import { useEffect } from 'react';
import { __, _x } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';

import { Grid } from 'common/grid';
import { Select } from 'common/form/select/select';
import { Input } from 'common/form/input/input';

import { EmailFormItem } from '../types';

function replaceElementsInDaysSentence(
  fn: (value) => JSX.Element,
): JSX.Element[] {
  return _x(
    'in the last {days} days',
    'The result will be "in the last 5 days"',
    'mailpoet',
  )
    .split(/({days})/gim)
    .map(fn);
}

function replaceEmailActionOpensSentence(
  fn: (value) => JSX.Element,
): JSX.Element[] {
  return _x(
    '{condition} {opens} opens',
    'The result will be "more than 20 opens"',
    'mailpoet',
  )
    .split(/({condition})|({opens})|(\b[a-zA-Z]+\b)/gim)
    .map(fn);
}

type Props = {
  filterIndex: number;
};

export function EmailOpensAbsoluteCountFields({
  filterIndex,
}: Props): JSX.Element {
  const segment: EmailFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } = useDispatch(
    'mailpoet-dynamic-segments-form',
  );
  useEffect(() => {
    if (segment.operator === undefined) {
      void updateSegmentFilter({ operator: 'more' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      <Grid.CenteredRow>
        {replaceEmailActionOpensSentence((match) => {
          if (match === '{condition}') {
            return (
              <Select
                key="select"
                value={segment.operator}
                onChange={(e) => {
                  void updateSegmentFilterFromEvent('operator', filterIndex, e);
                }}
              >
                <option value="more">{__('more than', 'mailpoet')}</option>
                <option value="less">{__('less than', 'mailpoet')}</option>
                <option value="equals">{__('equals', 'mailpoet')}</option>
                <option value="not_equals">
                  {__('no equals', 'mailpoet')}
                </option>
              </Select>
            );
          }
          if (match === '{opens}') {
            return (
              <Input
                key="input"
                type="number"
                value={segment.opens || ''}
                data-automation-id="segment-number-of-opens"
                onChange={(e) => {
                  void updateSegmentFilterFromEvent('opens', filterIndex, e);
                }}
                min="0"
                placeholder={__('opens', 'mailpoet')}
              />
            );
          }
          if (typeof match === 'string' && match.trim().length > 1) {
            return <div key="opens">{match}</div>;
          }
          return null;
        })}
      </Grid.CenteredRow>
      <Grid.CenteredRow>
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
                min="0"
                placeholder={__('days', 'mailpoet')}
              />
            );
          }
          if (typeof match === 'string' && match.trim().length > 1) {
            return <div key={match}>{match}</div>;
          }
          return null;
        })}
      </Grid.CenteredRow>
    </>
  );
}
