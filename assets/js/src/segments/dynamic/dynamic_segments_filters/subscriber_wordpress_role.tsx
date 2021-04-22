import React from 'react';
import { assign, compose, find } from 'lodash/fp';
import MailPoet from 'mailpoet';

import Select from 'common/form/react_select/react_select';
import { SegmentFormData } from '../segment_form_data';

import {
  WordpressRoleFormItem,
  OnFilterChange,
  SelectOption,
} from '../types';

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

export const WordpressRoleFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
  const options = SegmentFormData.wordpressRoles?.map((currentValue) => ({
    value: currentValue.role_id,
    label: currentValue.role_name,
  }));

  return (
    <Select
      isFullWidth
      placeholder={MailPoet.I18n.t('selectUserRolePlaceholder')}
      options={options}
      value={find(['value', item.wordpressRole], options)}
      onChange={(option: SelectOption): void => compose([
        onChange,
        assign(item),
      ])({ wordpressRole: option.value })}
      automationId="segment-wordpress-role"
    />
  );
};
