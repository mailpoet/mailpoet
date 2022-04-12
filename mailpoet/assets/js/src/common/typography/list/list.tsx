import { ReactNode } from 'react';

type Props = {
  children: ReactNode;
  isOrdered?: boolean;
};

export function List({ children, isOrdered }: Props) {
  const Element = isOrdered ? 'ol' : 'ul';
  return <Element className={`mailpoet-${Element}`}>{children}</Element>;
}
