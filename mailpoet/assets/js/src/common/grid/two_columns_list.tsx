import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
};

export function TwoColumnsList({ children, className }: Props): React.ReactElement {
  return (
    <div className={classnames(className, 'mailpoet-grid-two-columns-list')}>
      {children}
    </div>
  );
}
