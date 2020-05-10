import React from 'react';

type Props = {
  children?: React.ReactNode,
};

const ThreeColumns = ({ children }: Props) => (
  <div className="mailpoet-grid-three-columns">
    {children}
  </div>
);

export default ThreeColumns;
