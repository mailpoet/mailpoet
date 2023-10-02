import { Card, CardBody } from '@wordpress/components';
import { Heading } from 'common/typography';
import { ReactNode } from 'react';

type Props = {
  children: ReactNode;
  title: string;
  description: string;
};

export function FieldsSection({ children, title, description }: Props) {
  return (
    <div className="mailpoet-admin-fields">
      <div className="mailpoet-admin-fields-title">
        <Heading level={4}>{title}</Heading>
        <p>{description}</p>
      </div>
      <Card>
        <CardBody>{children}</CardBody>
      </Card>
    </div>
  );
}
