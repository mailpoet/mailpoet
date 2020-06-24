import React from 'react';

type Props = {
  children: React.ReactNode,
  isOrdered?: boolean,
};

const List = ({
  children,
  isOrdered,
}: Props) => {
  const Element = isOrdered ? 'ol' : 'ul';
  return React.createElement(
    Element,
    { className: `mailpoet-${Element}` },
    children
  );
};

export default List;
