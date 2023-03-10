import { useEffect } from 'react';
import { filter, map } from 'lodash/fp';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';

import { ReactSelect } from 'common/form/react_select/react_select';
import { Select } from 'common/form/select/select';
import { Grid } from 'common/grid';

import {
  WordpressRoleFormItem,
  SelectOption,
  WindowEditableRoles,
  AnyValueTypes,
  SubscriberActionTypes,
} from '../types';

type Props = {
  filterIndex: number;
};

export function WordpressRoleFields({ filterIndex }: Props): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } = useDispatch(
    'mailpoet-dynamic-segments-form',
  );

  useEffect(() => {
    if (
      segment.action === SubscriberActionTypes.WORDPRESS_ROLE &&
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  const wordpressRoles: WindowEditableRoles = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getWordpressRoles(),
    [],
  );
  const options = wordpressRoles.map((currentValue) => ({
    value: currentValue.role_id,
    label: currentValue.role_name,
  }));

  return (
    <>
      <Grid.CenteredRow>
        <Select
          key="select"
          isFullWidth
          value={segment.operator}
          onChange={(e) => {
            void updateSegmentFilterFromEvent('operator', filterIndex, e);
          }}
        >
          <option value={AnyValueTypes.ANY}>{__('any of', 'mailpoet')}</option>
          <option value={AnyValueTypes.ALL}>{__('all of', 'mailpoet')}</option>
          <option value={AnyValueTypes.NONE}>
            {__('none of', 'mailpoet')}
          </option>
        </Select>
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <ReactSelect
          dimension="small"
          isFullWidth
          isMulti
          automationId="segment-wordpress-role"
          placeholder={__('Search user roles', 'mailpoet')}
          options={options}
          value={filter((option) => {
            if (!segment.wordpressRole) return undefined;
            return segment.wordpressRole.indexOf(option.value) !== -1;
          }, options)}
          onChange={(selectOptions: SelectOption[]): void => {
            void updateSegmentFilter(
              { wordpressRole: map('value', selectOptions) },
              filterIndex,
            );
          }}
        />
      </Grid.CenteredRow>
    </>
  );
}
