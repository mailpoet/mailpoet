import React from 'react';

type Props = {
  children: React.ReactNode,
  level: 1 | 2 | 3 | 4 | 5,
};

const Heading = ({
  children,
  level,
}: Props) => (
  React.createElement(
    `h${level}`,
    { className: `mailpoet-h${level}` },
    children
  )
);

export default Heading;
