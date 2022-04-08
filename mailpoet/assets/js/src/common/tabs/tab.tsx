import { ReactNode } from 'react';

/* eslint-disable react/no-unused-prop-types -- all properties are used in the Tabs component */
type Props = {
  title?: string;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
  route?: string;
  automationId?: string;
  children: ReactNode;
};

function Tab({ children }: Props) {
  return <>{children}</>;
}

export default Tab;
