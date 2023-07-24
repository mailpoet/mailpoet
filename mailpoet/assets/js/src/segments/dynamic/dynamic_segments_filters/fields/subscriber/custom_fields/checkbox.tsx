import { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';

import {
  BlankOptions,
  FilterProps,
  isBlankOption,
  WordpressRoleFormItem,
} from '../../../../types';
import { storeName } from '../../../../store';

export function validateCheckbox(item: WordpressRoleFormItem): boolean {
  return ['1', '0'].includes(item.value) || isBlankOption(item.value);
}

export function Checkbox({ filterIndex }: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilterFromEvent, updateSegmentFilter } =
    useDispatch(storeName);

  useEffect(() => {
    if (!validateCheckbox(segment)) {
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
      <option value={BlankOptions.BLANK}>{MailPoet.I18n.t('isBlank')}</option>
      <option value={BlankOptions.NOT_BLANK}>
        {MailPoet.I18n.t('isNotBlank')}
      </option>
    </Select>
  );
}
