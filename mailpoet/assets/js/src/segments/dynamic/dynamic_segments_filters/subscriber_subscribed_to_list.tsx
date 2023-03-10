import { useEffect } from 'react';
import { filter, map } from 'lodash/fp';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { Select } from 'common/form/select/select';
import { Grid } from 'common/grid';
import { ReactSelect } from 'common/form/react_select/react_select';

import {
  AnyValueTypes,
  SelectOption,
  StaticSegment,
  WordpressRoleFormItem,
} from '../types';

export function validateSubscribedToList(
  formItems: WordpressRoleFormItem,
): boolean {
  return (
    (formItems.operator === AnyValueTypes.ANY ||
      formItems.operator === AnyValueTypes.ALL ||
      formItems.operator === AnyValueTypes.NONE) &&
    Array.isArray(formItems.segments) &&
    formItems.segments.length > 0
  );
}

type Props = {
  filterIndex: number;
};

export function SubscribedToList({ filterIndex }: Props): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const staticSegmentsList: StaticSegment[] = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getStaticSegmentsList(),
    [],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } = useDispatch(
    'mailpoet-dynamic-segments-form',
  );

  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);
  const options = staticSegmentsList.map((currentValue) => ({
    value: currentValue.id,
    label: currentValue.name,
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
          placeholder={__('Search lists', 'mailpoet')}
          options={options}
          value={filter((option) => {
            if (!segment.segments) return undefined;
            const segmentId = option.value;
            return segment.segments.indexOf(segmentId) !== -1;
          }, options)}
          onChange={(selectOptions: SelectOption[]): void => {
            void updateSegmentFilter(
              { segments: map('value', selectOptions) },
              filterIndex,
            );
          }}
        />
      </Grid.CenteredRow>
    </>
  );
}
