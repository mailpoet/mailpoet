import { useEffect } from 'react';
import { map, filter } from 'lodash/fp';
import { useDispatch, useSelect } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
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
          placeholder={MailPoet.I18n.t('searchLists')}
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
