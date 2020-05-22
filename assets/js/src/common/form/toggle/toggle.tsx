import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  dimension?: 'small',
  onCheck: (isChecked: boolean) => void,
};

const Toggle = ({
  dimension,
  onCheck,
  ...attributes
}: Props) => (
  <label
    className={
      classnames({
        'mailpoet-form-toggle': true,
        [`mailpoet-form-toggle-${dimension}`]: dimension,
        'mailpoet-disabled': attributes.disabled,
      })
    }
  >
    <input
      type="checkbox"
      onChange={(e) => onCheck(e.target.checked)}
      {...attributes}
    />
    <span className="mailpoet-form-toggle-control" />
  </label>
);

export default Toggle;
