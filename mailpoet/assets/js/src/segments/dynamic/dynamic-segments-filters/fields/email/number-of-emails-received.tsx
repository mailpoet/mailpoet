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

function replaceEmailActionNumberReceivedSentence(
  fn: (value) => JSX.Element,
): JSX.Element[] {
  return __('{condition} {emails} emails', 'mailpoet')
    .split(/({condition})|({emails})|(\b[a-zA-Z]+\b)/gim)
    .map(fn);
}

export function NumberOfEmailsReceivedFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: EmailFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);
  useEffect(() => {
    if (segment.operator === undefined) {
      void updateSegmentFilter({ operator: 'more' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      <Grid.CenteredRow>
        {replaceEmailActionNumberReceivedSentence((match) => {
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
          if (match === '{emails}') {
            return (
              <Input
                key="input"
                type="number"
                value={segment.emails || ''}
                data-automation-id="segment-number-emails-received"
                onChange={(e) => {
                  void updateSegmentFilterFromEvent('emails', filterIndex, e);
                }}
                min="0"
                placeholder={__('emails', 'mailpoet')}
              />
            );
          }
          if (typeof match === 'string' && match.trim().length > 1) {
            return <div key="emails">{match}</div>;
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
