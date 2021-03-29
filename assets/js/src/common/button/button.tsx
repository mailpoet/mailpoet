import React from 'react';
import classnames from 'classnames';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  children?: React.ReactNode;
  dimension?: 'extra-small' | 'small' | 'large';
  variant?: 'light' | 'dark' | 'link' | 'link-dark';
  withSpinner?: boolean; // also disables href and onClick (via pointer-events in CSS)
  isDisabled?: boolean; // also disables href and onClick (via pointer-events in CSS)
  isFullWidth?: boolean;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
  onClick?: () => void;
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
      className={
        classnames(
          className,
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
      data-automation-id={automationId}
    >
      {iconStart}
      {children && <span>{children}</span>}
      {iconEnd}
    </Element>
  );
};

export default Button;
