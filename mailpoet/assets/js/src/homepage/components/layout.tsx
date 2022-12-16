import { ReactNode } from 'react';

type Props = {
  children: ReactNode;
};

export function Layout({ children }: Props): JSX.Element {
  return <div className="mailpoet-homepage__container">{children}</div>;
}
