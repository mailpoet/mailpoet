import { ReactNode } from 'react';
import classnames from 'classnames';

type Props = {
  children?: ReactNode;
  variant?: 'average' | 'good' | 'excellent' | 'list' | 'unknown' | 'wordpress';
  dimension?: 'large';
  isInverted?: boolean;
  className?: string;
  data?: string;
};

export function Tag({
  children,
  variant,
  dimension,
  isInverted,
  className,
  ...dataAttributes
}: Props) {
  return (
    <div
      {...dataAttributes}
      className={classnames(className, 'mailpoet-tag', {
        [`mailpoet-tag-${variant}`]: variant,
        [`mailpoet-tag-${dimension}`]: dimension,
        'mailpoet-tag-inverted': isInverted,
      })}
    >
      {children}
    </div>
  );
}
