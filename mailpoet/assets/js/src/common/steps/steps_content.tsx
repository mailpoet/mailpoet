import { ReactNode } from 'react';

type Props = {
  children: ReactNode;
};

function StepsContent({ children }: Props) {
  return <div className="mailpoet-steps-content">{children}</div>;
}

export default StepsContent;
