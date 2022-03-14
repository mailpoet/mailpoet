import * as React from 'react';

type Props = {
  children: React.ReactNode;
};

function StepsContent({ children }: Props) {
  return <div className="mailpoet-steps-content">{children}</div>;
}

export default StepsContent;
