import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
};

export const TwoColumns = ({ children, className }: Props): React.ReactElement => (
  <div className={classnames(className, 'mailpoet-grid-two-columns')}>
    {children}
  </div>
);
