import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode,
  size?: 'small' | 'large',
  variant?: 'light' | 'dark' | 'link' | 'link-dark',
  withSpinner?: boolean, // also disables href and onClick (via pointer-events in CSS)
  isDisabled?: boolean, // also disables href and onClick (via pointer-events in CSS)
  isFullWidth?: boolean,
  iconStart?: JSX.Element,
  iconEnd?: JSX.Element,
  onClick?: () => void,
  href?: string,
};

const Button = ({
  children,
  size,
  variant,
  withSpinner,
  isDisabled,
  isFullWidth,
  iconStart,
  iconEnd,
  onClick,
  href,
}: Props) => (
  <a
    href={href}
    onClick={onClick}
    className={
      classnames(
        'mailpoet-button',
        {
          [`mailpoet-button-${size}`]: size,
          [`mailpoet-button-${variant}`]: variant,
          'mailpoet-button-disabled': isDisabled,
          'mailpoet-button-with-spinner': withSpinner,
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
