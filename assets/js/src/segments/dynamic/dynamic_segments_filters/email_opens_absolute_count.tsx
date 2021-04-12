import React, { useEffect } from 'react';
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
        <Select
          value={item.operator}
          onChange={(e): void => compose([
            onChange,
            assign(item),
          ])({ operator: e.target.value })}
        >
          <option value="more">{MailPoet.I18n.t('moreThan')}</option>
          <option value="less">{MailPoet.I18n.t('lessThan')}</option>
        </Select>
        <Input
          type="number"
          min="0"
          placeholder={MailPoet.I18n.t('emailActionOpens')}
        />
        <div>
          {MailPoet.I18n.t('emailActionOpens')}
        </div>
      </Grid.CenteredRow>
    </>
  );
};
