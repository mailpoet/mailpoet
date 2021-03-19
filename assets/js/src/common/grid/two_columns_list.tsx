import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
};

export const TwoColumnsList = ({ children, className }: Props) => (
  <div className={classnames(className, 'mailpoet-grid-two-columns-list')}>
    {children}
  </div>
);
