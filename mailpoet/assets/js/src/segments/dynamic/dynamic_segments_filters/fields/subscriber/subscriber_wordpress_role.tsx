import { useEffect } from 'react';
import { filter, map } from 'lodash/fp';
import { MailPoet } from 'mailpoet';
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
  FilterProps,
} from '../../../types';
import { storeName } from '../../../store';

export function WordpressRoleFields({ filterIndex }: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);

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
    (select) => select(storeName).getWordpressRoles(),
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
          <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
          <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
          <option value={AnyValueTypes.NONE}>
            {MailPoet.I18n.t('noneOf')}
          </option>
        </Select>
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <ReactSelect
          dimension="small"
          isFullWidth
          isMulti
          automationId="segment-wordpress-role"
          placeholder={MailPoet.I18n.t('selectUserRolePlaceholder')}
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
