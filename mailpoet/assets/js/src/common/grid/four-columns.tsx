import { ReactElement, ReactNode } from 'react';
import classnames from 'classnames';

type Props = {
  children?: ReactNode;
  className?: string;
  automationId?: string;
};

export function FourColumns({
  children,
  className,
  automationId,
}: Props): ReactElement {
  return (
    <div
      className={classnames(className, 'mailpoet-grid-four-columns')}
      data-automation-id={automationId}
    >
      {children}
    </div>
  );
}
