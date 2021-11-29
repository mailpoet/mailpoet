import React, { useEffect } from 'react';
import { map, filter } from 'lodash/fp';
import { useDispatch, useSelect } from '@wordpress/data';

import MailPoet from 'mailpoet';
import Select from 'common/form/select/select';
import ReactSelect from 'common/form/react_select/react_select';

import {
  AnyValueTypes,
  SelectOption,
  StaticSegment,
  WordpressRoleFormItem,
} from '../types';

export function validateSubscribedToList(formItems: WordpressRoleFormItem): boolean {
  return (
    (
      (formItems.operator === AnyValueTypes.ANY)
      || (formItems.operator === AnyValueTypes.ALL)
      || (formItems.operator === AnyValueTypes.NONE)
    )
    && (
      (Array.isArray(formItems.segments))
      && (formItems.segments.length > 0)
    )
  );
}

type Props = {
  filterIndex: number;
}

export const SubscribedToList: React.FunctionComponent<Props> = ({ filterIndex }) => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex]
  );
  const staticSegmentsList: StaticSegment[] = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getStaticSegmentsList(),
    []
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } = useDispatch('mailpoet-dynamic-segments-form');

  useEffect(() => {
    if (
      (
        (segment.operator !== AnyValueTypes.ANY)
        && (segment.operator !== AnyValueTypes.ALL)
        && (segment.operator !== AnyValueTypes.NONE)
      )
    ) {
      updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);
  const options = staticSegmentsList.map((currentValue) => ({
    value: currentValue.id,
    label: currentValue.name,
  }));

  return (
    <>
      <Select
        key="select"
        value={segment.operator}
        onChange={(e) => {
          updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
        <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
        <option value={AnyValueTypes.NONE}>{MailPoet.I18n.t('noneOf')}</option>
      </Select>
      <ReactSelect
        dimension="small"
        isFullWidth
        isMulti
        placeholder={MailPoet.I18n.t('searchLists')}
        options={options}
        value={
          filter(
            (option) => {
              if (!segment.segments) return undefined;
              const segmentId = option.value;
              return segment.segments.indexOf(segmentId) !== -1;
            },
            options
          )
        }
        onChange={(options: SelectOption[]): void => {
          updateSegmentFilter(
            { segments: map('value', options) },
            filterIndex
          );
        }}
      />
    </>
  );
};
