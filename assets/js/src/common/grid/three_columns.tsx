import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
};

const ThreeColumns = ({ children, className }: Props) => (
  <div className={classnames(className, 'mailpoet-grid-three-columns')}>
    {children}
  </div>
);

export default ThreeColumns;
