import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { Select } from 'common/form/select/select';

import { WordpressRoleFormItem } from '../../types';

export function validateCheckbox(item: WordpressRoleFormItem): boolean {
  return item.value === '1' || item.value === '0';
}

type Props = {
  filterIndex: number;
};

export function Checkbox({ filterIndex }: Props): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilterFromEvent, updateSegmentFilter } = useDispatch(
    'mailpoet-dynamic-segments-form',
  );

  useEffect(() => {
    if (segment.value !== '1' && segment.value !== '0') {
      void updateSegmentFilter({ operator: 'equals', value: '1' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <Select
      key="select"
      value={segment.value}
      onChange={(e) => updateSegmentFilterFromEvent('value', filterIndex, e)}
    >
      <option value="1">{__('checked', 'mailpoet')}</option>
      <option value="0">{__('unchecked', 'mailpoet')}</option>
    </Select>
  );
}
