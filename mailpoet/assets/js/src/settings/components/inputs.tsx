import { ReactNode } from 'react';

type Props = {
  children: ReactNode;
};

export default function Inputs({ children }: Props) {
  return <div className="mailpoet-settings-inputs">{children}</div>;
}
