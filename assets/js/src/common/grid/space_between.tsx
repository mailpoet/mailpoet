import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode,
  verticalAlign?: 'center',
};

const SpaceBetween = ({
  children,
  verticalAlign,
}: Props) => (
  <div
    className={
      classnames(
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
