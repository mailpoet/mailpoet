import React from 'react';

type Props = {
  children: React.ReactNode;
  isOrdered?: boolean;
};

const List = ({
  children,
  isOrdered,
}: Props) => {
  const Element = isOrdered ? 'ol' : 'ul';
  return (
    <Element className={`mailpoet-${Element}`}>
      {children}
    </Element>
  );
};

export default List;
