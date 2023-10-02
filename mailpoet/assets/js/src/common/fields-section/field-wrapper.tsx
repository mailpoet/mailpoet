import { ReactNode } from 'react';

type Props = {
  children: ReactNode;
  slug?: string;
  title?: string;
  description?: string;
};

export function FieldWrapper({ children, slug, title, description }: Props) {
  return (
    <div className="mailpoet-admin-field">
      {title && (
        <p>
          <label htmlFor={`field_${slug}`}>{title}</label>
        </p>
      )}
      <div>{children}</div>
      {description && (
        <p className="mailpoet-admin-field-description">{description}</p>
      )}
    </div>
  );
}
