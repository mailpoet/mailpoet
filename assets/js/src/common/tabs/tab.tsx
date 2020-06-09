import React from 'react';

type Props = {
  title?: string,
  iconStart?: JSX.Element,
  iconEnd?: JSX.Element,
  children: React.ReactNode
};

const Tab = ({
  title = null,
  iconStart = null,
  iconEnd = null,
  children,
}: Props) => (
  <>
    {children}
  </>
);

export default Tab;
