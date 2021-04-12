import React from 'react';

import {
  EmailFormItem,
  OnFilterChange,
} from '../types';

interface Props {
  onChange: OnFilterChange;
  item: EmailFormItem;
}

export const EmailOpensAbsoluteCountFields: React.FunctionComponent<Props> = ({
  onChange,
  item,
}) => (
  <>
    <div className="mailpoet-form-field">
      {item.name}
    </div>
  </>
);
