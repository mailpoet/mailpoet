import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  customLabel?: string,
  dimension?: 'small',
  isFullWidth?: boolean,
  iconStart?: JSX.Element,
  iconEnd?: JSX.Element,
};

const Input = ({
  className,
  customLabel,
  dimension,
  isFullWidth,
  iconStart,
  iconEnd,
  ...attributes
}: Props) => (
  <div
    className={
      classnames(
        className,
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
    {customLabel && <div className="mailpoet-form-input-label">{customLabel}</div>}
    {iconEnd}
  </div>
);

export default Input;
