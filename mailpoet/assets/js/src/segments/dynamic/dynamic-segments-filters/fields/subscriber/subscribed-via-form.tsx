import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';
import { Grid } from 'common/grid';
import { ReactSelect } from 'common/form/react_select/react_select';

import {
  AnyValueTypes,
  FilterProps,
  SelectOption,
  SignupForm,
  WordpressRoleFormItem,
} from '../../../types';
import { storeName } from '../../../store';

export function validateSubscribedViaForm(
  formItems: WordpressRoleFormItem,
): boolean {
  return (
    (formItems.operator === AnyValueTypes.ANY ||
      formItems.operator === AnyValueTypes.NONE) &&
    Array.isArray(formItems.form_ids) &&
    formItems.form_ids.length > 0
  );
}

export function SubscribedViaForm({ filterIndex }: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const signupForms: SignupForm[] = useSelect(
    (select) => select(storeName).getSignupForms(),
    [],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);
  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);
  const options = signupForms.map((form) => ({
    value: form.id,
    label: form.name,
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
          placeholder={MailPoet.I18n.t('searchForms')}
          options={options}
          value={options.filter((option) => {
            if (!segment.form_ids) {
              return undefined;
            }
            const formId = option.value;
            return segment.form_ids.indexOf(formId) !== -1;
          })}
          onChange={(selectOptions: SelectOption[]): void => {
            void updateSegmentFilter(
              { form_ids: selectOptions.map((option) => option.value) },
              filterIndex,
            );
          }}
        />
      </Grid.CenteredRow>
    </>
  );
}
