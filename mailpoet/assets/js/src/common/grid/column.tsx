import { ReactElement, ReactNode } from 'react';
import classnames from 'classnames';

type Props = {
  children?: ReactNode;
  align?: 'center';
  className?: string;
  dimension?: 'small';
};

export function Column({
  children,
  align,
  className,
  dimension,
}: Props): ReactElement {
  return (
    <div
      className={classnames(className, 'mailpoet-grid-column', {
        [`mailpoet-grid-column-${dimension}`]: dimension,
        [`mailpoet-grid-column-${align}`]: align,
      })}
    >
      {children}
    </div>
  );
}
