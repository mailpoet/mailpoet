import React from 'react';

type Props = {
  children?: React.ReactNode,
};

const TwoColumnsList = ({ children }: Props) => (
  <div className="mailpoet-grid-two-columns-list">
    {children}
  </div>
);

export default TwoColumnsList;
