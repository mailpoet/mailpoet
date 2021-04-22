import React from 'react';

import {
  WordpressRoleFormItem,
  OnFilterChange,
} from '../types';

interface Props {
  onChange: OnFilterChange;
  item: WordpressRoleFormItem;
}

export const SubscribedDateFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
  return (
    <div>SubscribedDateFields</div>
  );
};
