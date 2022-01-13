import React from 'react';

type Props = {
  children: React.ReactNode;
};

const StepsContent = ({ children }: Props) => (
  <div className="mailpoet-steps-content">{children}</div>
);

export default StepsContent;
