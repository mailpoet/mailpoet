import React, { useEffect } from 'react';
import { assign, compose, find } from 'lodash/fp';

import { Grid } from 'common/grid';
import MailPoet from 'mailpoet';

import {
  EmailFormItem,
  OnFilterChange, SelectOption,
} from '../types';
import Select from '../../../common/form/react_select/react_select';

interface Props {
  onChange: OnFilterChange;
  item: EmailFormItem;
}

const moreLessOptions = [
  {
    label: MailPoet.I18n.t('moreThan'),
    value: 'more',
  },
  {
    label: MailPoet.I18n.t('lessThan'),
    value: 'less',
  },
];

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
        <div className="mailpoet-form-field">
          <div className="mailpoet-form-input mailpoet-form-select" data-automation-id="segment-email">
            <Select
              options={moreLessOptions}
              value={find(['value', item.operator], moreLessOptions)}
              onChange={(option: SelectOption): void => compose([
                onChange,
                assign(item),
              ])({ operator: option.value })}
            />
          </div>
        </div>
      </Grid.CenteredRow>
    </>
  );
};
