import * as React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode;
  className?: string;
  automationId?: string;
};

export function ThreeColumns({ children, className, automationId }: Props): React.ReactElement {
  return (
    <div className={classnames(className, 'mailpoet-grid-three-columns')} data-automation-id={automationId}>
      {children}
    </div>
  );
}
