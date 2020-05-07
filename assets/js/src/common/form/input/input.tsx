import React from 'react';
import classnames from 'classnames';

type Props = {
  dimension?: 'small',
  isFullWidth?: boolean,
  iconStart?: JSX.Element,
  iconEnd?: JSX.Element,
  [attribute: string]: any, // any HTML attributes, e.g. type, name, id, placeholder
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
