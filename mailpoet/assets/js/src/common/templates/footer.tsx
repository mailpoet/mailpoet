import { ReactNode } from 'react';

type Props = {
  children: ReactNode;
};

export function Footer({ children }: Props): JSX.Element {
  return <div className="mailpoet-templates-footer">{children}</div>;
}
