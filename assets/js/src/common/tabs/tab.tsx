import React from 'react';

/* eslint-disable react/no-unused-prop-types */
type Props = {
  title?: string;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
  route?: string;
  automationId?: string;
  children: React.ReactNode;
};

const Tab = ({
  children,
}: Props) => (
  <>
    {children}
  </>
);

export default Tab;
