import React, { HTMLAttributes } from 'react';

type Props = HTMLAttributes<HTMLHeadingElement> & {
  children: React.ReactNode,
  level: 0 | 1 | 2 | 3 | 4 | 5,
};

const Heading = ({
  children,
  level,
  ...attributes
}: Props) => {
  // This is written like this because of linter errors on React's IntrinsicAttributes
  const Element = level === 5 ? 'h5' : level === 4 ? 'h4' : level === 3 ? 'h3' : level === 2 ? 'h2' : 'h1'; // eslint-disable-line no-nested-ternary
  return (
    <Element className={`mailpoet-h${level}`} {...attributes}>
      {children}
    </Element>
  );
};

export default Heading;
