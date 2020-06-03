import React from 'react';

type Props = {
  title: string,
  children: React.ReactNode
};

const Tab = ({
  title,
  children,
}: Props) => (
  <>
    {children}
  </>
);

export default Tab;
