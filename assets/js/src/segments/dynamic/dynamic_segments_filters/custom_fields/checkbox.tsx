import React, { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';

import MailPoet from 'mailpoet';
import Select from 'common/form/select/select';

import {
  WordpressRoleFormItem,
} from '../../types';

export function validateCheckbox(item: WordpressRoleFormItem): boolean {
  return ((item.value === '1') || (item.value === '0'));
}

export const Checkbox: React.FunctionComponent = () => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { updateSegmentFromEvent, updateSegment } = useDispatch('mailpoet-dynamic-segments-form');

  useEffect(() => {
    if ((segment.value !== '1') && (segment.value !== '0')) {
      updateSegment({ operator: 'equals', value: '1' });
    }
  }, [updateSegment, segment]);

  return (
    <>
      <div className="mailpoet-gap" />
      <Select
        key="select"
        value={segment.value}
        onChange={(e) => updateSegmentFromEvent('value', e)}
      >
        <option value="1">{MailPoet.I18n.t('checked')}</option>
        <option value="0">{MailPoet.I18n.t('unchecked')}</option>
      </Select>
    </>
  );
};
