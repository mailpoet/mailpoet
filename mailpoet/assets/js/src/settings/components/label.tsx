import { ReactNode } from 'react';

type Props = {
  title: string;
  description?: ReactNode;
  htmlFor?: string;
};

export function Label({ title, description, htmlFor }: Props) {
  return (
    <div className="mailpoet-settings-label">
      {htmlFor ? (
        <label className="mailpoet-settings-label-title" htmlFor={htmlFor}>
          {title}
        </label>
      ) : (
        <div className="mailpoet-settings-label-title">{title}</div>
      )}
      {description && <p className="description">{description}</p>}
    </div>
  );
}
