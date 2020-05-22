import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  dimension?: 'small',
  isFullWidth?: boolean,
  iconStart?: JSX.Element,
  iconEnd?: JSX.Element,
};

const Input = ({
  dimension,
  isFullWidth,
  iconStart,
  iconEnd,
  ...attributes
}: Props) => (
  <div
    className={
      classnames(
        'mailpoet-form-input',
        {
          [`mailpoet-form-input-${dimension}`]: dimension,
          'mailpoet-disabled': attributes.disabled,
          'mailpoet-full-width': isFullWidth,
        }
      )
    }
  >
    {iconStart}
    <input {...attributes} />
    {iconEnd}
  </div>
);

export default Input;
