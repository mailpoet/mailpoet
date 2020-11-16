import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
  verticalAlign?: 'center';
};

const SpaceBetween = ({
  children,
  className,
  verticalAlign,
}: Props) => (
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

export default SpaceBetween;
