import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';
import { Grid } from 'common/grid';
import { ReactSelect } from 'common/form/react-select/react-select';

import {
  AnyValueTypes,
  Automation,
  AutomationsFormItem,
  FilterProps,
  SelectOption,
} from '../../../types';
import { storeName } from '../../../store';

export function validateAutomationsFields(
  formItems: AutomationsFormItem,
): boolean {
  return (
    (formItems.operator === AnyValueTypes.ANY ||
      formItems.operator === AnyValueTypes.NONE ||
      formItems.operator === AnyValueTypes.ALL) &&
    Array.isArray(formItems.automation_ids) &&
    formItems.automation_ids.length > 0
  );
}

export function GeneralAutomationsFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: AutomationsFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const automations: Automation[] = useSelect(
    (select) => select(storeName).getAutomations(),
    [],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);
  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);
  const options = automations.map((automation) => ({
    value: automation.id,
    label: automation.name,
  }));

  return (
    <>
      <Grid.CenteredRow>
        <Select
          key="select"
          isFullWidth
          value={segment.operator}
          onChange={(e) => {
            void updateSegmentFilterFromEvent('operator', filterIndex, e);
          }}
        >
          <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
          <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
          <option value={AnyValueTypes.NONE}>
            {MailPoet.I18n.t('noneOf')}
          </option>
        </Select>
      </Grid.CenteredRow>
      <Grid.CenteredRow>
        <ReactSelect
          dimension="small"
          isFullWidth
          isMulti
          placeholder={MailPoet.I18n.t('searchAutomations')}
          options={options}
          value={options.filter((option) => {
            if (!segment.automation_ids) {
              return undefined;
            }
            const automationId = option.value;
            return segment.automation_ids.indexOf(automationId) !== -1;
          })}
          onChange={(selectOptions: SelectOption[]): void => {
            void updateSegmentFilter(
              { automation_ids: selectOptions.map((option) => option.value) },
              filterIndex,
            );
          }}
        />
      </Grid.CenteredRow>
    </>
  );
}
