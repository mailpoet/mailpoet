import React from 'react';
import classnames from 'classnames';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  children?: React.ReactNode;
  dimension?: 'small';
  variant?: 'secondary' | 'tertiary' | 'destructive';
  withSpinner?: boolean; // also disables href and onClick (via pointer-events in CSS)
  isDisabled?: boolean; // also disables href and onClick (via pointer-events in CSS)
  isFullWidth?: boolean;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
  onClick?: (event: React.MouseEvent<HTMLElement>) => void;
  href?: string;
  rel?: string;
  type?: 'button' | 'submit' | 'reset';
  target?: '_blank' | '_self' | '_parent' | '_top' | string;
  automationId?: string;
  className?: string;
}

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
  rel,
  type,
  target,
  automationId,
  className,
}: Props) => {
  const Element = href ? 'a' : 'button';
  return (
    <Element
      href={href}
      onClick={onClick}
      type={type}
      target={target}
      rel={rel}
      disabled={isDisabled}
      className={
        classnames(
          className,
          'button',
          'mailpoet-button',
          {
            [`mailpoet-button-${variant}`]: variant,
            'mailpoet-button-with-spinner': withSpinner,
            'mailpoet-button-disabled': isDisabled,
            'mailpoet-full-width': isFullWidth,
            'button-primary': !variant,
            'button-secondary': variant === 'secondary',
            'button-link': variant === 'tertiary',
            'button-link button-link-delete': variant === 'destructive',
            'button-small': dimension === 'small',
          }
        )
      }
      data-automation-id={automationId}
    >
      {iconStart}
      {children && <span>{children}</span>}
      {iconEnd}
    </Element>
  );
};

export default Button;
