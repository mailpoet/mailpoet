import { ReactNode } from 'react';

type Props = {
  title: string;
  description?: ReactNode;
  htmlFor: string;
};

export default function Label({ title, description, htmlFor }: Props) {
  return (
    <div className="mailpoet-settings-label">
      <label className="mailpoet-settings-label-title" htmlFor={htmlFor}>
        {title}
      </label>
      {description && <p className="description">{description}</p>}
    </div>
  );
}
