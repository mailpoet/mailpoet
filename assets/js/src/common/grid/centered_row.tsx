import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
};

const CenteredRow = ({ children, className }: Props) => (
  <div className={classnames(className, 'mailpoet-centered-row')}>
    {children}
  </div>
);

export default CenteredRow;
