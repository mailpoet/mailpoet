import React, { ReactNode } from 'react';

type Props = {
  title: string;
  description?: ReactNode;
  htmlFor: string;
}

export default ({ title, description, htmlFor }: Props) => (
  <div className="mailpoet-settings-label">
    <label className="mailpoet-settings-label-title" htmlFor={htmlFor}>{title}</label>
    {description && <p className="description">{description}</p>}
  </div>
);
