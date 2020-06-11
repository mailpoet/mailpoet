import React from 'react';

type Props = {
  title?: string,
  iconStart?: JSX.Element,
  iconEnd?: JSX.Element,
  route?: string,
  automationId?: string,
  children: React.ReactNode
};

const Tab = ({
  title = null,
  iconStart = null,
  iconEnd = null,
  route = null,
  automationId = null,
  children,
}: Props) => (
  <>
    {children}
  </>
);

export default Tab;
