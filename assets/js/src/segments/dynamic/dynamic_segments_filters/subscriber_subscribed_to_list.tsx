import React, { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';

import MailPoet from 'mailpoet';
import { Grid } from 'common/grid';
import Select from 'common/form/select/select';

import { AnyValueTypes, WordpressRoleFormItem } from '../types';

export function validateSubscribedToList(formItems: WordpressRoleFormItem): boolean {
  return (
    (formItems.operator !== AnyValueTypes.ANY)
    && (formItems.operator !== AnyValueTypes.ALL)
    && (formItems.operator !== AnyValueTypes.NONE)
  );
}

type Props = {
  filterIndex: number;
}

export const SubscribedToList: React.FunctionComponent<Props> = ({ filterIndex }) => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex]
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } = useDispatch('mailpoet-dynamic-segments-form');

  useEffect(() => {
    if (
      (
        (segment.operator !== AnyValueTypes.ANY)
        && (segment.operator !== AnyValueTypes.ALL)
        && (segment.operator !== AnyValueTypes.NONE)
      )
    ) {
      updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);

  return (
    <Grid.CenteredRow>
      <Select
        key="select"
        value={segment.operator}
        onChange={(e) => {
          updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
        <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
        <option value={AnyValueTypes.NONE}>{MailPoet.I18n.t('noneOf')}</option>
      </Select>
    </Grid.CenteredRow>
  );
};
