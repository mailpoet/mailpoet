import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  children?: React.ReactNode,
  isFullWidth?: boolean,
  onChange?: (boolean) => void,
};

const Radio = ({
  children,
  isFullWidth,
  onChange,
  ...attributes
}: Props) => (
  <label
    className={
      classnames({
        'mailpoet-form-radio': true,
        'mailpoet-full-width': isFullWidth,
      })
    }
  >
    <input
      type="radio"
      onChange={(e) => onChange(e.target.checked)}
      {...attributes}
    />
    <span className="mailpoet-form-radio-control" />
    {children}
  </label>
);

export default Radio;
