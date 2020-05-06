import React from 'react';
import classnames from 'classnames';

type Props = {
  size?: 'small',
  isFullWidth?: boolean,
  iconStart?: JSX.Element,
  iconEnd?: JSX.Element,
  [attribute: string]: any, // any HTML attributes, e.g. type, name, id, placeholder
};

const Input = ({
  size,
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
          [`mailpoet-form-input-${size}`]: size,
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
