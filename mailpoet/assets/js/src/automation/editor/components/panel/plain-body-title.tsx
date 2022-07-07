import { ReactNode } from 'react';

type Props = {
  title: string;
  children?: ReactNode;
};

export function PlainBodyTitle({ title, children }: Props): JSX.Element {
  return (
    <h2 className="components-panel__body-title mailpoet-automation-panel-plain-body-title">
      <div className="mailpoet-automation-panel-plain-body-title-text">
        {title}
      </div>
      {children}
    </h2>
  );
}
