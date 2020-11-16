import React, { ReactNode } from 'react';

type Props = {
  children: ReactNode;
}

export default ({ children }: Props) => (
  <div className="mailpoet-settings-inputs">{children}</div>
);
