import React from 'react';
import { assign, compose, find } from 'lodash/fp';
import MailPoet from 'mailpoet';
import { useSelect } from '@wordpress/data';

import Select from 'common/form/react_select/react_select';

import {
  WordpressRoleFormItem,
  OnFilterChange,
  SelectOption,
  WindowEditableRoles,
} from '../types';

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

export const WordpressRoleFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
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
            if (!item.wordpressRole) return undefined;
            return item.wordpressRole.toLowerCase() === option.value.toLowerCase();
          },
          options
        )
      }
      onChange={(option: SelectOption): void => compose([
        onChange,
        assign(item),
      ])({ wordpressRole: option.value })}
      automationId="segment-wordpress-role"
    />
  );
};
