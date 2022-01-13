import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  variant?: 'average' | 'good' | 'excellent' | 'list' | 'unknown';
  dimension?: 'large';
  isInverted?: boolean;
  className?: string;
  data?: string;
}

const Tag = ({
  children,
  variant,
  dimension,
  isInverted,
  className,
  ...dataAttributes
}: Props) => (
  <div
    {...dataAttributes}
    className={
      classnames(
        className,
        'mailpoet-tag',
        {
          [`mailpoet-tag-${variant}`]: variant,
          [`mailpoet-tag-${dimension}`]: dimension,
          'mailpoet-tag-inverted': isInverted,
        }
      )
    }
  >
    {children}
  </div>
);

export default Tag;
