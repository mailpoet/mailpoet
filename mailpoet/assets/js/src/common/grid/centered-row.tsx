import { ReactElement, ReactNode } from 'react';
import classnames from 'classnames';

type Props = {
  children?: ReactNode;
  className?: string;
};

export function CenteredRow({ children, className }: Props): ReactElement {
  return (
    <div className={classnames(className, 'mailpoet-centered-row')}>
      {children}
    </div>
  );
}
