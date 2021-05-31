import React, { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import MailPoet from 'mailpoet';
import Select from 'common/form/select/select';
import Input from 'common/form/input/input';
import { Grid } from 'common/grid';

import {
  WordpressRoleFormItem,
} from '../../types';

export function validateText(item: WordpressRoleFormItem): boolean {
  return (
    (typeof item.value === 'string')
    && (item.value.length > 0)
    && ((item.operator === 'equals') || (item.operator === 'contains'))
  );
}

export const Text: React.FunctionComponent = () => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { updateSegmentFromEvent, updateSegment } = useDispatch('mailpoet-dynamic-segments-form');

  useEffect(() => {
    if (segment.operator === undefined) {
      updateSegment({ operator: 'equals', value: '' });
    }
  }, [updateSegment, segment]);

  return (
    <>
      <Grid.CenteredRow>
        <Select
          key="select"
          automationId="text-custom-field-operator"
          value={segment.operator}
          onChange={(e) => {
            updateSegmentFromEvent('operator', e);
          }}
        >
          <option value="equals">{MailPoet.I18n.t('equals')}</option>
          <option value="contains">{MailPoet.I18n.t('contains')}</option>
        </Select>
        <Input
          key="input"
          data-automation-id="text-custom-field-value"
          value={segment.value || ''}
          onChange={(e) => {
            updateSegmentFromEvent('value', e);
          }}
          placeholder={MailPoet.I18n.t('value')}
        />
      </Grid.CenteredRow>
    </>
  );
};
