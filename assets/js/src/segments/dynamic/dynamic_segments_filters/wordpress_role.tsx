import React from 'react';
import { assign, compose, find } from 'lodash/fp';
import MailPoet from 'mailpoet';

import Select from 'common/form/react_select/react_select';

import {
  WordpressRoleFormItem,
  OnFilterChange,
  SegmentTypes,
  SelectOption,
} from '../types';

export function validateWordpressRole(formItems: WordpressRoleFormItem): boolean {
  return !!formItems.wordpressRole;
}

export const WordpressRoleSegmentOptions = [
  { value: 'wordpressRole', label: MailPoet.I18n.t('segmentsSubscriber'), group: SegmentTypes.WordPressRole },
];

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

interface WPRoleWindow extends Window {
  wordpress_editable_roles_list: {
    role_id: string;
    role_name: string;
  }[];
}

declare let window: WPRoleWindow;

export const WordpressRoleFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
  const options = window.wordpress_editable_roles_list.map((currentValue) => ({
    value: currentValue.role_id,
    label: currentValue.role_name,
  }));

  return (
    <div className="mailpoet-form-field">
      <div className="mailpoet-form-input mailpoet-form-select" data-automation-id="segment-wordpress-role">
        <Select
          placeholder={MailPoet.I18n.t('selectUserRolePlaceholder')}
          options={options}
          value={find(['value', item.wordpressRole], options)}
          onChange={(option: SelectOption): void => compose([
            onChange,
            assign(item),
          ])({ wordpressRole: option.value })}
        />
      </div>
    </div>
  );
};
