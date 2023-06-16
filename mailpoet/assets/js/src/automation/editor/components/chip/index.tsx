import { ReactNode } from 'react';
import classNames from 'classnames';

type Props = {
  variant?: 'default' | 'danger';
  size?: 'small' | 'medium' | 'large';
  children?: ReactNode;
};

export function Chip({
  variant = 'default',
  size = 'medium',
  children,
}: Props): JSX.Element {
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
    >
      {children}
    </div>
  );
}
