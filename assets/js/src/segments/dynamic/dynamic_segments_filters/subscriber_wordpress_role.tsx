import React from 'react';
import { find } from 'lodash/fp';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import Select from 'common/form/react_select/react_select';

import {
  WordpressRoleFormItem,
  SelectOption,
  WindowEditableRoles,
} from '../types';

export const WordpressRoleFields: React.FunctionComponent = () => {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getSegment(),
    []
  );

  const { updateSegment } = useDispatch('mailpoet-dynamic-segments-form');

  const wordpressRoles: WindowEditableRoles = useSelect(
    (select) => select('mailpoet-dynamic-segments-form').getWordpressRoles(),
    []
  );
  const options = wordpressRoles.map((currentValue) => ({
    value: currentValue.role_id,
    label: currentValue.role_name,
  }));

  return (
    <Select
      isFullWidth
      placeholder={MailPoet.I18n.t('selectUserRolePlaceholder')}
      options={options}
      value={
        find(
          (option) => {
            if (!segment.wordpressRole) return undefined;
            return segment.wordpressRole.toLowerCase() === option.value.toLowerCase();
          },
          options
        )
      }
      onChange={(option: SelectOption): void => {
        updateSegment({ wordpressRole: option.value });
      }}
      automationId="segment-wordpress-role"
    />
  );
};
