import { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import { Grid } from 'common/grid';
import { Select } from 'common/form/select/select';
import { Input } from 'common/form/input/input';
import { MailPoet } from 'mailpoet';

import { EmailFormItem } from '../types';

function replaceElementsInDaysSentence(
  fn: (value) => JSX.Element,
): JSX.Element[] {
  return MailPoet.I18n.t('emailActionOpensDaysSentence')
    .split(/({days})/gim)
    .map(fn);
}

function replaceEmailActionOpensSentence(
  fn: (value) => JSX.Element,
): JSX.Element[] {
  return MailPoet.I18n.t('emailActionOpensSentence')
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
                <option value="more">{MailPoet.I18n.t('moreThan')}</option>
                <option value="less">{MailPoet.I18n.t('lessThan')}</option>
                <option value="equals">{MailPoet.I18n.t('equals')}</option>
                <option value="not_equals">
                  {MailPoet.I18n.t('notEquals')}
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
                placeholder={MailPoet.I18n.t('emailActionOpens')}
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
                placeholder={MailPoet.I18n.t('emailActionDays')}
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
