import { HTMLAttributes, ReactNode } from 'react';
import classnames from 'classnames';

type Props = HTMLAttributes<HTMLHeadingElement> & {
  children: ReactNode;
  level: 0 | 1 | 2 | 3 | 4 | 5;
};

export function Heading({ children, level, className, ...attributes }: Props) {
  const Element = level === 0 ? 'h1' : (`h${level}` as const);
  return (
    <Element
      className={classnames(className, `mailpoet-h${level}`)}
      {...attributes}
    >
      {children}
    </Element>
  );
}
