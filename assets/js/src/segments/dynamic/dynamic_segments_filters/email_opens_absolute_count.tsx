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

export const EmailOpensAbsoluteCountFields: React.FunctionComponent = () => {
  const segment: EmailFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { updateSegment, updateSegmentFromEvent } = useDispatch('mailpoet-dynamic-segments-form');
  useEffect(() => {
    if (segment.operator === undefined) {
      updateSegment({ operator: 'more' });
    }
  }, [updateSegment, segment]);

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
                    updateSegmentFromEvent('operator', e);
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
                  value={segment.opens}
                  data-automation-id="segment-number-of-opens"
                  onChange={(e) => {
                    updateSegmentFromEvent('opens', e);
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
                  value={segment.days}
                  data-automation-id="segment-number-of-days"
                  onChange={(e) => {
                    updateSegmentFromEvent('days', e);
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
