import { ReactNode } from 'react';
import classNames from 'classnames';

type Props = {
  size?: 'small' | 'medium' | 'large';
  children?: ReactNode;
};

export function Chip({ size = 'medium', children }: Props): JSX.Element {
  return (
    <div
      className={classNames({
        'mailpoet-automation-chip': true,
        'chip-small': size === 'small',
        'chip-medium': size === 'medium',
        'chip-large': size === 'large',
      })}
    >
      {children}
    </div>
  );
}
