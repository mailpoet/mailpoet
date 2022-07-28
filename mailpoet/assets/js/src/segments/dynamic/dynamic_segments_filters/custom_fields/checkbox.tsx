import { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
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
      <option value="1">{MailPoet.I18n.t('checked')}</option>
      <option value="0">{MailPoet.I18n.t('unchecked')}</option>
    </Select>
  );
}
