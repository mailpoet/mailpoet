import * as React from 'react';

type Props = {
  children: React.ReactNode;
  isOrdered?: boolean;
};

function List({
  children,
  isOrdered,
}: Props) {
  const Element = isOrdered ? 'ol' : 'ul';
  return (
    <Element className={`mailpoet-${Element}`}>
      {children}
    </Element>
  );
}

export default List;
