import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode,
  variant?: 'average' | 'good' | 'excellent' | 'list',
  dimension?: 'large',
  isInverted?: boolean,
  data?: string,
}

const Tag = ({
  children,
  variant,
  dimension,
  isInverted,
  ...dataAttributes
}: Props) => (
  <div
    {...dataAttributes}
    className={
      classnames(
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
