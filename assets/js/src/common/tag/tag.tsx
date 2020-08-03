import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode,
  variant?: 'average' | 'good' | 'excellent',
  isInverted?: boolean,
  data?: string,
}

const Tag = ({
  children,
  variant,
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
          'mailpoet-tag-inverted': isInverted,
        }
      )
    }
  >
    {children}
  </div>
);

export default Tag;
