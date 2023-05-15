import { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';

import { FilterProps, WordpressRoleFormItem } from '../../../../types';
import { storeName } from '../../../../store';

export function validateCheckbox(item: WordpressRoleFormItem): boolean {
  return item.value === '1' || item.value === '0';
}

export function Checkbox({ filterIndex }: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilterFromEvent, updateSegmentFilter } =
    useDispatch(storeName);

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
      <option value="1">{MailPoet.I18n.t('checked')}</option>
      <option value="0">{MailPoet.I18n.t('unchecked')}</option>
    </Select>
  );
}
