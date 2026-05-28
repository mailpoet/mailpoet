import { ReactNode } from 'react';

/* eslint-disable react/no-unused-prop-types -- all properties are used in the Tabs component */
export type TabProps = {
  title?: string;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
  route?: string;
  automationId?: string;
  children: ReactNode;
  className?: string;
};

export function Tab({ children }: TabProps) {
  return <>{children}</>;
}
