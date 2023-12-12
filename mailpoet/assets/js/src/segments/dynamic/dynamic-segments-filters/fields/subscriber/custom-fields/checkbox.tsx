import { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import { Select } from 'common/form/select/select';

import { Grid } from 'common/grid';
import { __ } from '@wordpress/i18n';
import {
  BlankOptions,
  FilterProps,
  isBlankOption,
  WordpressRoleFormItem,
} from '../../../../types';
import { storeName } from '../../../../store';

export function validateCheckbox(item: WordpressRoleFormItem): boolean {
  if (isBlankOption(item.operator)) {
    return true;
  }
  return ['1', '0'].includes(item.value);
}

export function Checkbox({ filterIndex }: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilterFromEvent, updateSegmentFilter } =
    useDispatch(storeName);

  useEffect(() => {
    if (segment.operator === undefined) {
      void updateSegmentFilter({ operator: 'equals', value: '1' }, filterIndex);
    } else if (
      segment.operator === 'equals' &&
      !['1', '0'].includes(segment.value)
    ) {
      void updateSegmentFilter({ value: '1' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <Grid.CenteredRow>
      <Select
        key="select-operator"
        value={segment.operator}
        onChange={(e) => {
          void updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value="equals">{__('is', 'mailpoet')}</option>
        <option value={BlankOptions.BLANK}>{__('is blank', 'mailpoet')}</option>
        <option value={BlankOptions.NOT_BLANK}>
          {__('is not blank', 'mailpoet')}
        </option>
      </Select>
      {!isBlankOption(segment.operator) && (
        <Select
          key="select"
          value={segment.value}
          onChange={(e) =>
            updateSegmentFilterFromEvent('value', filterIndex, e)
          }
        >
          <option value="1">{__('checked', 'mailpoet')}</option>
          <option value="0">{__('unchecked', 'mailpoet')}</option>
        </Select>
      )}
    </Grid.CenteredRow>
  );
}
