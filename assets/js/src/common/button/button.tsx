import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode,
  dimension?: 'small' | 'large',
  variant?: 'light' | 'dark' | 'link' | 'link-dark',
  withSpinner?: boolean, // also disables href and onClick (via pointer-events in CSS)
  isDisabled?: boolean, // also disables href and onClick (via pointer-events in CSS)
  isFullWidth?: boolean,
  iconStart?: JSX.Element,
  iconEnd?: JSX.Element,
  onClick?: () => void,
  href?: string,
  target?: '_blank' | '_self' | '_parent' | '_top' | string,
};

const Button = ({
  children,
  dimension,
  variant,
  withSpinner,
  isDisabled,
  isFullWidth,
  iconStart,
  iconEnd,
  onClick,
  href,
  target,
}: Props) => (
  <a
    href={href}
    onClick={onClick}
    target={target}
    className={
      classnames(
        'mailpoet-button',
        {
          [`mailpoet-button-${dimension}`]: dimension,
          [`mailpoet-button-${variant}`]: variant,
          'mailpoet-button-with-spinner': withSpinner,
          'mailpoet-disabled': isDisabled,
          'mailpoet-full-width': isFullWidth,
        }
      )
    }
  >
    {iconStart}
    {children && <span>{children}</span>}
    {iconEnd}
  </a>
);

export default Button;
