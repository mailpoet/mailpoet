import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  children?: React.ReactNode,
  isFullWidth?: boolean,
  onChange?: (boolean) => void,
};

const Checkbox = ({
  children,
  isFullWidth,
  onChange,
  ...attributes
}: Props) => (
  <label
    className={
      classnames({
        'mailpoet-form-checkbox': true,
        'mailpoet-full-width': isFullWidth,
      })
    }
  >
    <input
      type="checkbox"
      onChange={(e) => onChange(e.target.checked)}
      {...attributes}
    />
    <span className="mailpoet-form-checkbox-control" />
    {children}
  </label>
);

export default Checkbox;
