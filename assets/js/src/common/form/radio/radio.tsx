import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  onCheck: (isChecked: boolean) => void,
  children?: React.ReactNode,
  isFullWidth?: boolean,
};

const Radio = ({
  children,
  isFullWidth,
  onCheck,
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
      onChange={(e) => onCheck(e.target.checked)}
      {...attributes}
    />
    <span className="mailpoet-form-radio-control" />
    {children}
  </label>
);

export default Radio;
