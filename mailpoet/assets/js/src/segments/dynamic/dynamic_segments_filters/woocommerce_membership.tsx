import { useEffect } from 'react';
import { MailPoet } from 'mailpoet';
import { filter } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import { ReactSelect } from 'common/form/react_select/react_select';
import { Select } from 'common/form/select/select';
import { Grid } from 'common/grid';

import {
  AnyValueTypes,
  SegmentTypes,
  SelectOption,
  WindowMembershipPlans,
  WooCommerceMembershipFormItem,
} from '../types';

enum WooCommerceMembershipsActionTypes {
  MEMBER_OF = 'isMemberOf',
}

export const WooCommerceMembershipOptions = [
  {
    value: WooCommerceMembershipsActionTypes.MEMBER_OF,
    label: MailPoet.I18n.t('segmentsActiveMembership'),
    group: SegmentTypes.WooCommerceMembership,
  },
];

export function validateWooCommerceMembership(
  formItem: WooCommerceMembershipFormItem,
): boolean {
  const isIncomplete =
    !formItem.plan_ids || !formItem.plan_ids.length || !formItem.operator;
  if (
    formItem.action === WooCommerceMembershipsActionTypes.MEMBER_OF &&
    isIncomplete
  ) {
    return false;
  }
  return true;
}

type Props = {
  filterIndex: number;
};

export function WooCommerceMembershipFields({
  filterIndex,
}: Props): JSX.Element {
  const segment: WooCommerceMembershipFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } = useDispatch(
    'mailpoet-dynamic-segments-form',
  );

  const membershipPlans: WindowMembershipPlans = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getMembershipPlans(),
    [],
  );
  const planOptions = membershipPlans.map((plan) => ({
    value: plan.id,
    label: plan.name,
  }));

  useEffect(() => {
    if (
      segment.action === WooCommerceMembershipsActionTypes.MEMBER_OF &&
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <>
      <Grid.CenteredRow>
        <Select
          key="select-operator"
          value={segment.operator}
          onChange={(e) =>
            updateSegmentFilterFromEvent('operator', filterIndex, e)
          }
          automationId="select-operator"
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
          isMulti
          dimension="small"
          key="select-segment-membership-plan"
          isFullWidth
          placeholder={MailPoet.I18n.t('selectWooMembership')}
          options={planOptions}
          value={filter((option) => {
            if (!segment.plan_ids) return false;
            return segment.plan_ids.indexOf(option.value) !== -1;
          }, planOptions)}
          onChange={(options: SelectOption[]): void => {
            void updateSegmentFilter(
              { plan_ids: (options || []).map((x: SelectOption) => x.value) },
              filterIndex,
            );
          }}
          automationId="select-segment-plans"
        />
      </Grid.CenteredRow>
    </>
  );
}
