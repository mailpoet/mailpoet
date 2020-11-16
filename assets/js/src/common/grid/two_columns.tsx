import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
};

const TwoColumns = ({ children, className }: Props) => (
  <div className={classnames(className, 'mailpoet-grid-two-columns')}>
    {children}
  </div>
);

export default TwoColumns;
