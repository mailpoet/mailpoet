import { ReactNode } from 'react';

type Props = {
  children: ReactNode;
};

export function Grid({ children }: Props): JSX.Element {
  return <div className="mailpoet-templates-card-grid">{children}</div>;
}
