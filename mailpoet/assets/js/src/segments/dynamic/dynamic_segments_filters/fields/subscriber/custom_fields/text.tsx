import { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';
import { Input } from 'common/form/input/input';
import { Grid } from 'common/grid';

import { FilterProps, WordpressRoleFormItem } from '../../../../types';
import { storeName } from '../../../../store';

export function validateText(item: WordpressRoleFormItem): boolean {
  if (['is_blank', 'is_not_blank'].includes(item.value)) {
    return true;
  }
  return (
    typeof item.value === 'string' &&
    item.value.length > 0 &&
    (item.operator === 'equals' ||
      item.operator === 'contains' ||
      item.operator === 'not_contains' ||
      item.operator === 'not_equals' ||
      item.operator === 'more_than' ||
      item.operator === 'less_than')
  );
}

export function Text({ filterIndex }: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilterFromEvent, updateSegmentFilter } =
    useDispatch(storeName);

  useEffect(() => {
    if (segment.operator === undefined) {
      void updateSegmentFilter({ operator: 'equals', value: '' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  const isUsingBlankOption = ['is_blank', 'is_not_blank'].includes(
    segment.operator,
  );

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
        <option value="equals">{MailPoet.I18n.t('is')}</option>
        <option value="not_equals">{MailPoet.I18n.t('isNot')}</option>
        <option value="contains">{MailPoet.I18n.t('contains')}</option>
        <option value="not_contains">{MailPoet.I18n.t('notContains')}</option>
        <option value="more_than">{MailPoet.I18n.t('moreThan')}</option>
        <option value="less_than">{MailPoet.I18n.t('lessThan')}</option>
        <option value="is_blank">{MailPoet.I18n.t('isBlank')}</option>
        <option value="is_not_blank">{MailPoet.I18n.t('isNotBlank')}</option>
      </Select>
      {!isUsingBlankOption && (
        <Input
          key="input"
          data-automation-id="text-custom-field-value"
          value={segment.value || ''}
          onChange={(e) => {
            void updateSegmentFilterFromEvent('value', filterIndex, e);
          }}
          placeholder={MailPoet.I18n.t('value')}
        />
      )}
    </Grid.CenteredRow>
  );
}
