import { ReactElement, ReactNode } from 'react';
import classnames from 'classnames';

type Props = {
  children?: ReactNode;
  className?: string;
};

export function TwoColumnsList({ children, className }: Props): ReactElement {
  return (
    <div className={classnames(className, 'mailpoet-grid-two-columns-list')}>
      {children}
    </div>
  );
}
