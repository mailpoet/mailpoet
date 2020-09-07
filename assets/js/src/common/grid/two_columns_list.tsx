import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode,
  className?: string,
};

const TwoColumnsList = ({ children, className }: Props) => (
  <div className={classnames(className, "mailpoet-grid-two-columns-list")}>
    {children}
  </div>
);

export default TwoColumnsList;
