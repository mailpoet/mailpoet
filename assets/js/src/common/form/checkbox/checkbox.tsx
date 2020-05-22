import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  onCheck: (isChecked: boolean) => void,
  children?: React.ReactNode,
  isFullWidth?: boolean,
};

const Checkbox = ({
  children,
  isFullWidth,
  onCheck,
  ...attributes
}: Props) => (
  <label
    className={
      classnames({
        'mailpoet-form-checkbox': true,
        'mailpoet-disabled': attributes.disabled,
        'mailpoet-full-width': isFullWidth,
      })
    }
  >
    <input
      type="checkbox"
      onChange={(e) => onCheck(e.target.checked)}
      {...attributes}
    />
    <span className="mailpoet-form-checkbox-control" />
    {children}
  </label>
);

export default Checkbox;
