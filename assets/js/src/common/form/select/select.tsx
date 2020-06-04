import React, { SelectHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = SelectHTMLAttributes<HTMLSelectElement> & {
  children?: React.ReactNode,
  dimension?: 'small',
  isFullWidth?: boolean,
  isMinWidth?: boolean,
  iconStart?: JSX.Element,
};

const Select = ({
  children,
  dimension,
  isFullWidth,
  isMinWidth,
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
          'mailpoet-min-width': isMinWidth,
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
