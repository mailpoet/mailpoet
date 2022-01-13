import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
  verticalAlign?: 'center';
};

export const SpaceBetween = ({
  children,
  className,
  verticalAlign,
}: Props): React.ReactElement => (
  <div
    className={
      classnames(
        className,
        'mailpoet-grid-space-between',
        {
          [`mailpoet-grid-space-between-vertical-${verticalAlign}`]: verticalAlign,
        }
      )
    }
  >
    {children}
  </div>
);
