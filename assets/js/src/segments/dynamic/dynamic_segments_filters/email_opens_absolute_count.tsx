import React, { useEffect } from 'react';
import ReactStringReplace from 'react-string-replace';
import { useSelect, useDispatch } from '@wordpress/data';

import { Grid } from 'common/grid';
import Select from 'common/form/select/select';
import Input from 'common/form/input/input';
import MailPoet from 'mailpoet';

import {
  EmailFormItem,
} from '../types';

function replaceElementsInDaysSentence(fn): JSX.Element[] {
  return MailPoet.I18n.t('emailActionOpensDaysSentence')
    .split(/({days})/gim)
    .map(fn);
}

type Props = {
  filterIndex: number;
}

export const EmailOpensAbsoluteCountFields: React.FunctionComponent<Props> = ({ filterIndex }) => {
  const segment: EmailFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex]
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } = useDispatch('mailpoet-dynamic-segments-form');
  useEffect(() => {
    if (segment.operator === undefined) {
      updateSegmentFilter({ operator: 'more' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      <Grid.CenteredRow>
        {ReactStringReplace(
          MailPoet.I18n.t('emailActionOpensSentence'),
          // ReactStringReplace is buggy, a simpler version of this regex doesn't work
          /({condition})|( )|({opens})|( )|(\b[a-zA-Z]+\b)/gim,
          (match) => {
            if (match === '{condition}') {
              return (
                <Select
                  key="select"
                  value={segment.operator}
                  onChange={(e) => {
                    updateSegmentFilterFromEvent('operator', filterIndex, e);
                  }}
                >
                  <option value="more">{MailPoet.I18n.t('moreThan')}</option>
                  <option value="less">{MailPoet.I18n.t('lessThan')}</option>
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
                    updateSegmentFilterFromEvent('opens', filterIndex, e);
                  }}
                  min="0"
                  placeholder={MailPoet.I18n.t('emailActionOpens')}
                />
              );
            }
            if ((typeof match === 'string') && match.trim().length > 1) {
              return (
                <div key="opens">
                  {match}
                </div>
              );
            }
            return null;
          }
        )}
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        {replaceElementsInDaysSentence(
          (match) => {
            if (match === '{days}') {
              return (
                <Input
                  key="input"
                  type="number"
                  value={segment.days || ''}
                  data-automation-id="segment-number-of-days"
                  onChange={(e) => {
                    updateSegmentFilterFromEvent('days', filterIndex, e);
                  }}
                  min="0"
                  placeholder={MailPoet.I18n.t('emailActionDays')}
                />
              );
            }
            if ((typeof match === 'string') && match.trim().length > 1) {
              return (
                <div key={match}>
                  {match}
                </div>
              );
            }
            return null;
          }
        )}
      </Grid.CenteredRow>
    </>
  );
};
