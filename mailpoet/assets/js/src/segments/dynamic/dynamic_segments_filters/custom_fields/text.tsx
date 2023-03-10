import { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { Select } from 'common/form/select/select';
import { Input } from 'common/form/input/input';
import { Grid } from 'common/grid';

import { WordpressRoleFormItem } from '../../types';

export function validateText(item: WordpressRoleFormItem): boolean {
  return (
    typeof item.value === 'string' &&
    item.value.length > 0 &&
    (item.operator === 'equals' ||
      item.operator === 'contains' ||
      item.operator === 'not_equals' ||
      item.operator === 'more_than' ||
      item.operator === 'less_than')
  );
}

type Props = {
  filterIndex: number;
};

export function Text({ filterIndex }: Props): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilterFromEvent, updateSegmentFilter } = useDispatch(
    'mailpoet-dynamic-segments-form',
  );

  useEffect(() => {
    if (segment.operator === undefined) {
      void updateSegmentFilter({ operator: 'equals', value: '' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <Grid.CenteredRow>
      <Select
        key="select"
        automationId="text-custom-field-operator"
        value={segment.operator}
        onChange={(e) => {
          void updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value="equals">{__('equals', 'mailpoet')}</option>
        <option value="not_equals">{__('not equals', 'mailpoet')}</option>
        <option value="contains">{__('contains', 'mailpoet')}</option>
        <option value="more_than">{__('more than', 'mailpoet')}</option>
        <option value="less_than">{__('less than', 'mailpoet')}</option>
      </Select>
      <Input
        key="input"
        data-automation-id="text-custom-field-value"
        value={segment.value || ''}
        onChange={(e) => {
          void updateSegmentFilterFromEvent('value', filterIndex, e);
        }}
        placeholder={__('value', 'mailpoet')}
      />
    </Grid.CenteredRow>
  );
}
