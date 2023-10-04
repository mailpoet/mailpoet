import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { Select } from 'common/form/select/select';
import { Input } from 'common/form/input/input';
import { MailPoet } from 'mailpoet';
import { FilterProps, TextFormItem } from '../../types';
import { storeName } from '../../store';

const validOperators = [
  'is',
  'isNot',
  'contains',
  'notContains',
  'startsWith',
  'notStartsWith',
  'endsWith',
  'notEndsWith',
];

export function validateTextField(formItems: TextFormItem): boolean {
  if (!validOperators.includes(formItems.operator)) {
    return false;
  }
  return typeof formItems.value === 'string' && formItems.value.length > 0;
}

export function TextField({ filterIndex }: FilterProps): JSX.Element {
  const segment: TextFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilterFromEvent, updateSegmentFilter } =
    useDispatch(storeName);

  useEffect(() => {
    if (!validOperators.includes(segment.operator)) {
      void updateSegmentFilter({ operator: 'is', value: '' }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      <Select
        key="select"
        automationId="subscriber-text-field-select"
        value={segment.operator}
        isMinWidth
        onChange={(e) => {
          void updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value="is">{MailPoet.I18n.t('is')}</option>
        <option value="isNot">{MailPoet.I18n.t('isNot')}</option>
        <option value="contains">{MailPoet.I18n.t('contains')}</option>
        <option value="notContains">{MailPoet.I18n.t('notContains')}</option>
        <option value="startsWith">{MailPoet.I18n.t('startsWith')}</option>
        <option value="notStartsWith">
          {MailPoet.I18n.t('notStartsWith')}
        </option>
        <option value="endsWith">{MailPoet.I18n.t('endsWith')}</option>
        <option value="notEndsWith">{MailPoet.I18n.t('notEndsWith')}</option>
      </Select>
      <Input
        className="mailpoet-segments-input-medium"
        key="input"
        data-automation-id="text-custom-field-value"
        value={segment.value || ''}
        onChange={(e) => {
          void updateSegmentFilterFromEvent('value', filterIndex, e);
        }}
        placeholder={MailPoet.I18n.t('value')}
      />
    </>
  );
}
