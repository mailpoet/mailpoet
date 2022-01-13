import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
};

export const CenteredRow = ({ children, className }: Props): React.ReactElement => (
  <div className={classnames(className, 'mailpoet-centered-row')}>
    {children}
  </div>
);
