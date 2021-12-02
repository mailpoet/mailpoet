import React from 'react';
import { filter, map } from 'lodash/fp';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import ReactSelect from 'common/form/react_select/react_select';
import { Grid } from 'common/grid';

import {
  WordpressRoleFormItem,
  SelectOption,
  WindowEditableRoles,
} from '../types';

type Props = {
  filterIndex: number;
}

export const WordpressRoleFields: React.FunctionComponent<Props> = ({ filterIndex }) => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex]
  );

  const { updateSegmentFilter } = useDispatch('mailpoet-dynamic-segments-form');

  const wordpressRoles: WindowEditableRoles = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getWordpressRoles(),
    []
  );
  const options = wordpressRoles.map((currentValue) => ({
    value: currentValue.role_id,
    label: currentValue.role_name,
  }));

  return (
    <>
      <Grid.CenteredRow>
        <ReactSelect
          dimension="small"
          isFullWidth
          isMulti
          automationId="segment-wordpress-role"
          placeholder={MailPoet.I18n.t('selectUserRolePlaceholder')}
          options={options}
          value={
            filter(
              (option) => {
                if (!segment.wordpressRole) return undefined;
                return segment.wordpressRole.indexOf(option.value) !== -1;
              },
              options
            )
          }
          onChange={(options: SelectOption[]): void => {
            updateSegmentFilter(
              { wordpressRole: map('value', options) },
              filterIndex
            );
          }}
        />
      </Grid.CenteredRow>
    </>
  );
};
