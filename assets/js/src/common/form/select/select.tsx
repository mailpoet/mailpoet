import React, { SelectHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = SelectHTMLAttributes<HTMLSelectElement> & {
  children?: React.ReactNode,
  dimension?: 'small',
  isFullWidth?: boolean,
  iconStart?: JSX.Element,
};

const Select = ({
  children,
  dimension,
  isFullWidth,
  iconStart,
  ...attributes
}: Props) => (
  <div
    className={
      classnames(
        'mailpoet-form-input',
        'mailpoet-form-select',
        {
          [`mailpoet-form-input-${dimension}`]: dimension,
          'mailpoet-disabled': attributes.disabled,
          'mailpoet-full-width': isFullWidth,
        }
      )
    }
  >
    {iconStart}
    <select {...attributes}>
      {children}
    </select>
  </div>
);

export default Select;
