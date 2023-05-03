import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import { Grid } from 'common/grid';
import { Select } from 'common/form/select/select';
import { Input } from 'common/form/input/input';
import { MailPoet } from '../../../mailpoet';
import { WordpressRoleFormItem } from '../types';
import { storeName } from '../store';

type Props = {
  filterIndex: number;
};

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

export function validateSubscriberTextField(
  formItems: WordpressRoleFormItem,
): boolean {
  if (!validOperators.includes(formItems.operator)) {
    return false;
  }
  return typeof formItems.value === 'string' && formItems.value.length > 0;
}

export function SubscriberTextField({ filterIndex }: Props): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
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
    <Grid.CenteredRow>
      <Select
        key="select"
        automationId="subscriber-text-field-select"
        value={segment.operator}
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
        key="input"
        data-automation-id="text-custom-field-value"
        value={segment.value || ''}
        onChange={(e) => {
          void updateSegmentFilterFromEvent('value', filterIndex, e);
        }}
        placeholder={MailPoet.I18n.t('value')}
      />
    </Grid.CenteredRow>
  );
}
