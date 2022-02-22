import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
};

export function CenteredRow({ children, className }: Props): React.ReactElement {
  return (
    <div className={classnames(className, 'mailpoet-centered-row')}>
      {children}
    </div>
  );
}
