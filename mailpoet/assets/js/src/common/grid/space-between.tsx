import { ReactElement, ReactNode } from 'react';
import classnames from 'classnames';

type Props = {
  children?: ReactNode;
  className?: string;
  verticalAlign?: 'center';
};

export function SpaceBetween({
  children,
  className,
  verticalAlign,
}: Props): ReactElement {
  return (
    <div
      className={classnames(className, 'mailpoet-grid-space-between', {
        [`mailpoet-grid-space-between-vertical-${verticalAlign}`]:
          verticalAlign,
      })}
    >
      {children}
    </div>
  );
}
