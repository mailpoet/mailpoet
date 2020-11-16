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
  children,
}: Props) => (
  <>
    {children}
  </>
);

export default Tab;
