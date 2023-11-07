import { EventHandler, MouseEvent } from 'react';
import { Button, Card, CardBody, CardHeader } from '@wordpress/components';
import { Tag } from '@woocommerce/components';
import { ItemBadge } from './item-badge';

type Props = {
  name: string;
  description: string;
  category: string;
  badge?: 'essential' | 'coming-soon' | 'premium';
  onClick?: EventHandler<MouseEvent<HTMLDivElement>>;
};

export function Item({
  name,
  description,
  category,
  badge,
  onClick,
}: Props): JSX.Element {
  return (
    <Card className="mailpoet-templates-card" onClick={onClick}>
      <CardHeader className="mailpoet-templates-card-header">
        {badge && <ItemBadge type={badge} />}
        <Button variant="link">{name}</Button>
      </CardHeader>
      <CardBody className="mailpoet-templates-card-body">
        <p>{description}</p>
        <Tag label={category} />
      </CardBody>
    </Card>
  );
}
