import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode,
  size?: 'small',
  isFullWidth?: boolean,
  iconStart?: JSX.Element,
  [attribute: string]: any, // any HTML attributes, e.g. name, id
};

const Select = ({
  children,
  size,
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
          [`mailpoet-form-input-${size}`]: size,
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
