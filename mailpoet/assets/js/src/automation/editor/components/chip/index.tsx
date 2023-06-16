import { MouseEvent, KeyboardEvent, ReactNode } from 'react';
import classNames from 'classnames';

type Props = {
  variant?: 'default' | 'danger';
  size?: 'small' | 'medium' | 'large';
  ariaExpanded?: boolean;
  onClick?: (
    event: MouseEvent<HTMLDivElement> | KeyboardEvent<HTMLDivElement>,
  ) => void;
  children?: ReactNode;
};

export function Chip({
  variant = 'default',
  size = 'medium',
  ariaExpanded = false,
  onClick,
  children,
}: Props): JSX.Element {
  const interactivityProps = onClick
    ? ({
        role: 'button',
        tabIndex: 0,
        onClick,
        onKeyDown: (event: KeyboardEvent<HTMLDivElement>) => {
          event.stopPropagation();
          event.preventDefault();
          if (['Enter', ' '].includes(event.key) && onClick) {
            onClick(event);
          }
        },
      } as const)
    : {};

  return (
    <div
      className={classNames({
        'mailpoet-automation-chip': true,
        'chip-small': size === 'small',
        'chip-medium': size === 'medium',
        'chip-large': size === 'large',
        'chip-default': variant === 'default',
        'chip-danger': variant === 'danger',
      })}
      aria-expanded={ariaExpanded}
      {...interactivityProps}
    >
      {children}
    </div>
  );
}
