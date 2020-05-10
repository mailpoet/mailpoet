import React from 'react';

type Props = {
  children?: React.ReactNode,
};

const TwoColumns = ({ children }: Props) => (
  <div className="mailpoet-grid-two-columns">
    {children}
  </div>
);

export default TwoColumns;
