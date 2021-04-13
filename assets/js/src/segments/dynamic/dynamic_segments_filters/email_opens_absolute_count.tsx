import React, { useEffect } from 'react';
import ReactStringReplace from 'react-string-replace';
import { assign, compose } from 'lodash/fp';

import { Grid } from 'common/grid';
import Select from 'common/form/select/select';
import Input from 'common/form/input/input';
import MailPoet from 'mailpoet';

import {
  EmailFormItem,
  OnFilterChange,
} from '../types';

interface Props {
  onChange: OnFilterChange;
  item: EmailFormItem;
}

function replaceElementsInDaysSentence(fn): JSX.Element[] {
  return MailPoet.I18n.t('emailActionOpensDaysSentence')
    .split(/({days})/gim)
    .map(fn);
}

export const EmailOpensAbsoluteCountFields: React.FunctionComponent<Props> = ({
  onChange,
  item,
}) => {
  useEffect(() => {
    if (item.operator === undefined) {
      onChange(assign(item, { operator: 'more' }));
    }
  }, [onChange, item, item.operator]);

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
                  value={item.operator}
                  onChange={(e): void => compose([
                    onChange,
                    assign(item),
                  ])({ operator: e.target.value })}
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
                  value={item.opens}
                  onChange={(e): void => compose([
                    onChange,
                    assign(item),
                  ])({ opens: e.target.value })}
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
                  value={item.days}
                  onChange={(e): void => compose([
                    onChange,
                    assign(item),
                  ])({ days: e.target.value })}
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
