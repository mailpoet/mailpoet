import { ReactNode } from 'react';

type Props = {
  children: ReactNode;
};

export function StepsContent({ children }: Props) {
  return <div className="mailpoet-steps-content">{children}</div>;
}
